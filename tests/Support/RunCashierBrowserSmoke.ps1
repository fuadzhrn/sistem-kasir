$ErrorActionPreference = 'Stop'

$baseUrl = 'http://sistem-kasir.test'
$chromePath = 'C:\Program Files\Google\Chrome\Application\chrome.exe'
$profilePath = Join-Path $env:TEMP ('codex-stage13-browser-' + [guid]::NewGuid().ToString('N'))
$debugPort = 9337
$chromeProcess = $null
$socket = $null
$fixturePrepared = $false

function Send-CdpCommand {
    param(
        [System.Net.WebSockets.ClientWebSocket] $Socket,
        [int] $Id,
        [string] $Method,
        [hashtable] $Params = @{}
    )

    $payload = @{
        id = $Id
        method = $Method
        params = $Params
    } | ConvertTo-Json -Depth 10 -Compress
    $bytes = [System.Text.Encoding]::UTF8.GetBytes($payload)
    $segment = [System.ArraySegment[byte]]::new($bytes)
    [void] $Socket.SendAsync(
        $segment,
        [System.Net.WebSockets.WebSocketMessageType]::Text,
        $true,
        [System.Threading.CancellationToken]::None
    ).GetAwaiter().GetResult()
}

function Receive-CdpMessage {
    param(
        [System.Net.WebSockets.ClientWebSocket] $Socket,
        [int] $TimeoutMilliseconds = 5000
    )

    $buffer = New-Object byte[] 65536
    $builder = [System.Text.StringBuilder]::new()
    $cancellation = [System.Threading.CancellationTokenSource]::new($TimeoutMilliseconds)

    try {
        do {
            $segment = [System.ArraySegment[byte]]::new($buffer)
            $result = $Socket.ReceiveAsync($segment, $cancellation.Token).GetAwaiter().GetResult()

            if ($result.MessageType -eq [System.Net.WebSockets.WebSocketMessageType]::Close) {
                return $null
            }

            [void] $builder.Append([System.Text.Encoding]::UTF8.GetString($buffer, 0, $result.Count))
        } while (-not $result.EndOfMessage)
    } finally {
        $cancellation.Dispose()
    }

    return $builder.ToString() | ConvertFrom-Json
}

function Wait-CdpResponse {
    param(
        [System.Net.WebSockets.ClientWebSocket] $Socket,
        [int] $CommandId,
        [System.Collections.Generic.List[string]] $Errors
    )

    while ($true) {
        $message = Receive-CdpMessage -Socket $Socket

        if ($null -eq $message) {
            throw 'Koneksi DevTools ditutup sebelum respons diterima.'
        }

        if ($message.method -in @('Runtime.exceptionThrown', 'Log.entryAdded')) {
            $Errors.Add($message.method)
        }

        if (
            $message.method -eq 'Runtime.consoleAPICalled' -and
            $message.params.type -eq 'error'
        ) {
            $Errors.Add('Runtime.consoleAPICalled:error')
        }

        if ($message.id -eq $CommandId) {
            return $message
        }
    }
}

try {
    if (-not (Test-Path -LiteralPath $chromePath)) {
        throw 'Google Chrome tidak ditemukan.'
    }

    $fixture = (& php .\tests\Support\BrowserCheckoutFixture.php prepare) |
        ConvertFrom-Json
    $fixturePrepared = $fixture.success -eq $true

    if (-not $fixturePrepared) {
        throw 'Fixture browser tidak dapat disiapkan.'
    }

    $accountDocument = Get-Content -LiteralPath 'docs/akun-login-testing.md' -Raw
    $accountMatch = [regex]::Match(
        $accountDocument,
        '\| Kasir \| `(?<login>[^`]+)` \| `(?<password>[^`]+)` \|'
    )

    if (-not $accountMatch.Success) {
        throw 'Akun Kasir testing tidak ditemukan.'
    }

    $webSession = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $loginPage = Invoke-WebRequest -Uri ($baseUrl + '/login') -WebSession $webSession -UseBasicParsing
    $csrfMatch = [regex]::Match($loginPage.Content, 'name="_token"\s+value="([^"]+)"')

    if (-not $csrfMatch.Success) {
        throw 'Token CSRF login tidak ditemukan.'
    }

    $loginBody = @{
        _token = $csrfMatch.Groups[1].Value
        login = $accountMatch.Groups['login'].Value
        password = $accountMatch.Groups['password'].Value
    }
    $loginRequest = @{
        Uri = $baseUrl + '/login'
        Method = 'Post'
        Body = $loginBody
        WebSession = $webSession
        UseBasicParsing = $true
    }
    [void] (Invoke-WebRequest @loginRequest)

    $chromeArguments = @(
        '--headless=new',
        '--disable-gpu',
        '--no-first-run',
        '--disable-extensions',
        "--remote-debugging-port=$debugPort",
        "--user-data-dir=$profilePath",
        'about:blank'
    )
    $startParameters = @{
        FilePath = $chromePath
        ArgumentList = $chromeArguments
        WindowStyle = 'Hidden'
        PassThru = $true
    }
    $chromeProcess = Start-Process @startParameters

    $targets = $null

    for ($attempt = 0; $attempt -lt 50 -and $null -eq $targets; $attempt++) {
        Start-Sleep -Milliseconds 100

        try {
            $targets = Invoke-RestMethod -Uri "http://127.0.0.1:$debugPort/json/list"
        } catch {
            $targets = $null
        }
    }

    if ($null -eq $targets -or $targets.Count -eq 0) {
        throw 'Target DevTools Chrome tidak tersedia.'
    }

    $pageTarget = $targets |
        Where-Object { $_.type -eq 'page' -and $_.url -notlike 'chrome-extension://*' } |
        Select-Object -First 1

    if ($null -eq $pageTarget) {
        throw 'Target halaman Chrome tidak tersedia.'
    }

    $socket = [System.Net.WebSockets.ClientWebSocket]::new()
    [void] $socket.ConnectAsync(
        [uri] $pageTarget.webSocketDebuggerUrl,
        [System.Threading.CancellationToken]::None
    ).GetAwaiter().GetResult()
    $errors = [System.Collections.Generic.List[string]]::new()
    $commandId = 0

    foreach ($command in @('Runtime.enable', 'Log.enable', 'Network.enable', 'Page.enable')) {
        $commandId++
        Send-CdpCommand -Socket $socket -Id $commandId -Method $command
        [void] (Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors)
    }

    foreach ($cookie in $webSession.Cookies.GetCookies([uri] $baseUrl)) {
        $commandId++
        Send-CdpCommand -Socket $socket -Id $commandId -Method 'Network.setCookie' -Params @{
            name = $cookie.Name
            value = $cookie.Value
            url = $baseUrl
            path = '/'
            httpOnly = $cookie.HttpOnly
            secure = $cookie.Secure
        }
        $cookieResponse = Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors

        if ($cookieResponse.result.success -ne $true) {
            $errors.Add('Network.setCookie:failed')
        }
    }

    $cookieHeader = ($webSession.Cookies.GetCookies([uri] $baseUrl) | ForEach-Object {
        $_.Name + '=' + $_.Value
    }) -join '; '
    $commandId++
    Send-CdpCommand -Socket $socket -Id $commandId -Method 'Network.setExtraHTTPHeaders' -Params @{
        headers = @{
            Cookie = $cookieHeader
        }
    }
    [void] (Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors)

    $commandId++
    Send-CdpCommand -Socket $socket -Id $commandId -Method 'Page.navigate' -Params @{
        url = $baseUrl + '/cashier'
    }
    [void] (Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors)
    Start-Sleep -Seconds 2

    $commandId++
    Send-CdpCommand -Socket $socket -Id $commandId -Method 'Runtime.evaluate' -Params @{
        expression = @'
({
    location: window.location.href,
    readyState: document.readyState,
    hasCashierRoot: Boolean(document.querySelector('[data-cashier-root]')),
    hasCheckoutUrl: Boolean(document.querySelector('[data-checkout-url]')),
    hasPrintAction: Boolean(document.querySelector('[data-payment-action="print"]')),
    hasNoPrintAction: Boolean(document.querySelector('[data-payment-action="no_print"]')),
    pageText: document.body.innerText.includes('Kasir aktif')
})
'@
        returnByValue = $true
    }
    $evaluation = Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors
    $value = $evaluation.result.result.value

    Write-Output ('BROWSER_LOCATION=' + $value.location)
    Write-Output ('BROWSER_READY_STATE=' + $value.readyState)
    Write-Output ('BROWSER_CASHIER_ROOT=' + $value.hasCashierRoot)
    Write-Output ('BROWSER_CHECKOUT_URL=' + $value.hasCheckoutUrl)
    Write-Output ('BROWSER_PRINT_ACTION=' + $value.hasPrintAction)
    Write-Output ('BROWSER_NO_PRINT_ACTION=' + $value.hasNoPrintAction)
    Write-Output ('BROWSER_CASHIER_ACTIVE_TEXT=' + $value.pageText)
    Write-Output ('BROWSER_CONSOLE_ERROR_COUNT=' + $errors.Count)

    if (
        -not $value.hasCashierRoot -or
        -not $value.hasCheckoutUrl -or
        $errors.Count -gt 0
    ) {
        throw 'Halaman kasir tidak siap untuk pengujian checkout.'
    }

    $invoices = [System.Collections.Generic.List[string]]::new()

    foreach ($paymentAction in @('no_print', 'print')) {
        $commandId++
        Send-CdpCommand -Socket $socket -Id $commandId -Method 'Runtime.evaluate' -Params @{
            expression = @'
(() => {
    const cards = Array.from(document.querySelectorAll('[data-product-id]'));
    const card = cards.find((candidate) => candidate.textContent.includes('STAGE13-BROWSER-001'));
    const button = card?.querySelector('[data-add-product]');

    if (!button || button.disabled) {
        return { added: false };
    }

    button.click();

    return { added: true };
})()
'@
            returnByValue = $true
        }
        $addResult = Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors

        if ($addResult.result.result.value.added -ne $true) {
            throw 'Produk fixture tidak dapat ditambahkan ke keranjang.'
        }

        Start-Sleep -Milliseconds 250
        $commandId++
        $checkoutExpression = @"
(() => {
    const received = document.querySelector('[data-amount-received]');
    const button = document.querySelector('[data-payment-action="$paymentAction"]');
    received.value = '50000';
    received.dispatchEvent(new Event('input', { bubbles: true }));
    const disabled = button.disabled;

    if (!disabled) {
        button.click();
    }

    return {
        buttonDisabled: disabled,
        cartItems: document.querySelectorAll('[data-cart-items] [data-product-id]').length
    };
})()
"@
        Send-CdpCommand -Socket $socket -Id $commandId -Method 'Runtime.evaluate' -Params @{
            expression = $checkoutExpression
            returnByValue = $true
        }
        $checkoutStart = Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors

        if ($checkoutStart.result.result.value.buttonDisabled -eq $true) {
            throw "Tombol $paymentAction masih disabled."
        }

        $invoice = $null

        for ($attempt = 0; $attempt -lt 50 -and $null -eq $invoice; $attempt++) {
            Start-Sleep -Milliseconds 200
            $commandId++
            Send-CdpCommand -Socket $socket -Id $commandId -Method 'Runtime.evaluate' -Params @{
                expression = @'
(() => {
    const modal = document.getElementById('cashier-payment-preview-modal');
    const invoice = document.querySelector('[data-preview-invoice]')?.textContent || '';
    const error = document.querySelector('[data-payment-error]')?.textContent || '';

    return {
        modalOpen: Boolean(modal && !modal.hidden),
        invoice,
        error
    };
})()
'@
                returnByValue = $true
            }
            $checkoutState = Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors
            $state = $checkoutState.result.result.value

            if (
                $state.modalOpen -eq $true -and
                $state.invoice -and
                -not $invoices.Contains([string] $state.invoice)
            ) {
                $invoice = [string] $state.invoice
            } elseif ($state.error -and $state.error -ne 'Keranjang masih kosong.') {
                throw ('Checkout browser ditolak: ' + $state.error)
            }
        }

        if ($null -eq $invoice) {
            throw "Checkout $paymentAction tidak menghasilkan invoice."
        }

        $invoices.Add($invoice)
        Write-Output ('BROWSER_CHECKOUT_' + $paymentAction.ToUpper() + '=success')
        $commandId++
        Send-CdpCommand -Socket $socket -Id $commandId -Method 'Runtime.evaluate' -Params @{
            expression = "document.querySelector('#cashier-payment-preview-modal [data-modal-close]').click()"
            returnByValue = $true
        }
        [void] (Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors)
        Start-Sleep -Milliseconds 500
    }

    $fixtureVerification = (& php .\tests\Support\BrowserCheckoutFixture.php verify) |
        ConvertFrom-Json
    Write-Output ('BROWSER_SALE_COUNT=' + $fixtureVerification.sale_count)
    Write-Output ('BROWSER_SALE_ITEM_COUNT=' + $fixtureVerification.sale_item_count)
    Write-Output ('BROWSER_MOVEMENT_COUNT=' + $fixtureVerification.movement_count)
    Write-Output ('BROWSER_ACTIVITY_LOG_COUNT=' + $fixtureVerification.activity_log_count)
    Write-Output ('BROWSER_STOCK_FINAL=' + $fixtureVerification.stock_final)
    Write-Output ('BROWSER_NEGATIVE_STOCK_COUNT=' + $fixtureVerification.negative_stock_count)
    Write-Output ('BROWSER_FINAL_CONSOLE_ERROR_COUNT=' + $errors.Count)

    if (
        $fixtureVerification.sale_count -ne 2 -or
        $fixtureVerification.sale_item_count -ne 2 -or
        $fixtureVerification.movement_count -ne 2 -or
        $fixtureVerification.activity_log_count -ne 2 -or
        $fixtureVerification.stock_final -ne '3.000' -or
        $fixtureVerification.negative_stock_count -ne 0 -or
        $errors.Count -ne 0
    ) {
        throw 'Hasil checkout browser tidak konsisten.'
    }
} finally {
    if ($null -ne $socket) {
        $socket.Dispose()
    }

    if ($null -ne $chromeProcess -and -not $chromeProcess.HasExited) {
        Stop-Process -Id $chromeProcess.Id -Force -ErrorAction SilentlyContinue
        Start-Sleep -Milliseconds 500
    }

    if (Test-Path -LiteralPath $profilePath) {
        $resolvedProfile = (Resolve-Path -LiteralPath $profilePath).Path
        $resolvedTemp = (Resolve-Path -LiteralPath $env:TEMP).Path

        if (
            $resolvedProfile.StartsWith($resolvedTemp, [StringComparison]::OrdinalIgnoreCase) -and
            $resolvedProfile -ne $resolvedTemp
        ) {
            Remove-Item -LiteralPath $resolvedProfile -Recurse -Force -ErrorAction SilentlyContinue
        }
    }

    if ($fixturePrepared) {
        $cleanup = (& php .\tests\Support\BrowserCheckoutFixture.php cleanup) |
            ConvertFrom-Json
        Write-Output ('BROWSER_FIXTURE_CLEANED=' + (
            $cleanup.product_remaining -eq 0 -and
            $cleanup.category_remaining -eq 0 -and
            $cleanup.unit_remaining -eq 0
        ))
    }
}
