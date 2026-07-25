param(
    [ValidateSet('Chrome', 'Edge')]
    [string] $Browser = 'Chrome'
)

$ErrorActionPreference = 'Stop'
$baseUrl = 'http://sistem-kasir.test'
$chromePath = if ($Browser -eq 'Edge') {
    'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
} else {
    'C:\Program Files\Google\Chrome\Application\chrome.exe'
}
$profilePath = Join-Path $env:TEMP ('codex-stage15-' + $Browser.ToLowerInvariant() + '-' + [guid]::NewGuid().ToString('N'))
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

        if ($message.method -eq 'Runtime.exceptionThrown') {
            $description = [string] $message.params.exceptionDetails.exception.description
            $Errors.Add(('Runtime.exceptionThrown:' + $description))
        }

        if (
            $message.method -eq 'Log.entryAdded' -and
            $message.params.entry.level -eq 'error'
        ) {
            $Errors.Add(('Log.entryAdded:' + [string] $message.params.entry.text))
        }

        if (
            $message.method -eq 'Runtime.consoleAPICalled' -and
            $message.params.type -eq 'error'
        ) {
            $consoleText = ($message.params.args | ForEach-Object {
                if ($null -ne $_.value) {
                    [string] $_.value
                } else {
                    [string] $_.description
                }
            }) -join ' '
            $Errors.Add(('Runtime.consoleAPICalled:error:' + $consoleText))
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
        '--disable-popup-blocking',
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

    foreach ($browserError in $errors) {
        Write-Output ('BROWSER_ERROR=' + $browserError)
    }

    if (
        -not $value.hasCashierRoot -or
        -not $value.hasCheckoutUrl -or
        $errors.Count -gt 0
    ) {
        throw 'Halaman kasir tidak siap untuk pengujian checkout.'
    }

    $invoices = [System.Collections.Generic.List[string]]::new()

    $scenarios = @(
        @{ name = 'NO_PRINT'; action = 'no_print'; blockPopup = $false; methodType = 'cash' },
        @{ name = 'PRINT'; action = 'print'; blockPopup = $false; methodType = 'cash' },
        @{ name = 'PRINT_BLOCKED'; action = 'print'; blockPopup = $true; methodType = 'cash' },
        @{ name = 'NO_PRINT_NONCASH'; action = 'no_print'; blockPopup = $false; methodType = 'non_cash' }
    )
    $receiptTargetCount = 0

    foreach ($scenario in $scenarios) {
        $paymentAction = $scenario.action
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
        $blockPopup = if ($scenario.blockPopup) { 'true' } else { 'false' }
        $methodType = $scenario.methodType
        $checkoutExpression = @"
(() => {
    const received = document.querySelector('[data-amount-received]');
    const method = document.querySelector('[data-payment-method]');
    const button = document.querySelector('[data-payment-action="$paymentAction"]');
    const targetMethod = Array.from(method.options).find((option) => option.dataset.type === '$methodType');

    if (targetMethod) {
        method.value = targetMethod.value;
        method.dispatchEvent(new Event('change', { bubbles: true }));
    }

    if ('$methodType' === 'cash') {
        received.value = '50000';
        received.dispatchEvent(new Event('input', { bubbles: true }));
    }

    if ($blockPopup) {
        window.__stage15OriginalOpen = window.open;
        window.open = () => null;
    }

    const disabled = button.disabled;

    if (!disabled) {
        button.click();
    }

    return {
        buttonDisabled: disabled,
        cartItems: document.querySelectorAll('[data-cart-items] [data-product-id]').length,
        methodFound: Boolean(targetMethod)
    };
})()
"@
        Send-CdpCommand -Socket $socket -Id $commandId -Method 'Runtime.evaluate' -Params @{
            expression = $checkoutExpression
            returnByValue = $true
        }
        $checkoutStart = Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors

        if (
            $checkoutStart.result.result.value.buttonDisabled -eq $true -or
            $checkoutStart.result.result.value.methodFound -ne $true
        ) {
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
    const printLink = document.querySelector('[data-preview-print-link]');

    return {
        modalOpen: Boolean(modal && !modal.hidden),
        invoice,
        error,
        fallbackVisible: Boolean(printLink && !printLink.hidden),
        method: document.querySelector('[data-preview-method]')?.textContent || '',
        change: document.querySelector('[data-preview-change]')?.textContent || ''
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

        if ($scenario.blockPopup -and $state.fallbackVisible -ne $true) {
            throw 'Fallback popup blocker tidak tampil.'
        }

        if (
            $scenario.methodType -eq 'non_cash' -and
            ($state.method -eq 'Tunai' -or $state.change -ne 'Rp0')
        ) {
            throw 'Ringkasan transaksi non-tunai tidak sesuai.'
        }

        if ($scenario.blockPopup) {
            $commandId++
            Send-CdpCommand -Socket $socket -Id $commandId -Method 'Runtime.evaluate' -Params @{
                expression = 'window.open = window.__stage15OriginalOpen; delete window.__stage15OriginalOpen'
                returnByValue = $true
            }
            [void] (Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors)
        }

        $targetsAfterCheckout = Invoke-RestMethod -Uri "http://127.0.0.1:$debugPort/json/list"
        $nextReceiptTargetCount = @(
            $targetsAfterCheckout | Where-Object { $_.url -like ($baseUrl + '/receipts/*/print*') }
        ).Count

        if ($scenario.name -eq 'PRINT') {
            for (
                $targetAttempt = 0;
                $targetAttempt -lt 20 -and $nextReceiptTargetCount -lt 1;
                $targetAttempt++
            ) {
                Start-Sleep -Milliseconds 100
                $targetsAfterCheckout = Invoke-RestMethod -Uri "http://127.0.0.1:$debugPort/json/list"
                $nextReceiptTargetCount = @(
                    $targetsAfterCheckout |
                        Where-Object { $_.url -like ($baseUrl + '/receipts/*/print*') }
                ).Count
            }
        }

        Write-Output ('BROWSER_CHECKOUT_' + $scenario.name + '_FALLBACK=' + $state.fallbackVisible)

        if ($scenario.name -eq 'NO_PRINT' -and $nextReceiptTargetCount -ne 0) {
            throw 'Bayar Tanpa Cetak membuka tab struk.'
        }

        if ($scenario.name -eq 'PRINT' -and $nextReceiptTargetCount -lt 1) {
            throw 'Bayar & Cetak tidak membuka tab struk.'
        }

        if ($scenario.blockPopup -and $nextReceiptTargetCount -ne $receiptTargetCount) {
            throw 'Skenario popup diblokir tetap membuka tab baru.'
        }

        $receiptTargetCount = $nextReceiptTargetCount
        $invoices.Add($invoice)
        Write-Output ('BROWSER_CHECKOUT_' + $scenario.name + '=success')
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
        $fixtureVerification.sale_count -ne 4 -or
        $fixtureVerification.sale_item_count -ne 4 -or
        $fixtureVerification.movement_count -ne 4 -or
        $fixtureVerification.activity_log_count -ne 4 -or
        $fixtureVerification.stock_final -ne '1.000' -or
        $fixtureVerification.negative_stock_count -ne 0 -or
        $errors.Count -ne 0
    ) {
        throw 'Hasil checkout browser tidak konsisten.'
    }

    foreach ($invoice in $invoices) {
        if ($invoice -notmatch '^[A-Z0-9]+-\d{8}-\d{4}$' -or $invoice.StartsWith('INV-')) {
            throw ('Format nomor nota final tidak sesuai: ' + $invoice)
        }
    }

    $commandId++
    Send-CdpCommand -Socket $socket -Id $commandId -Method 'Page.navigate' -Params @{
        url = $baseUrl + '/sales'
    }
    [void] (Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors)
    Start-Sleep -Seconds 2

    $commandId++
    Send-CdpCommand -Socket $socket -Id $commandId -Method 'Runtime.evaluate' -Params @{
        expression = @'
(() => {
    const detailLink = document.querySelector('a[href*="/sales/"]:not([href*="/receipt"])');
    const receiptLink = document.querySelector('a[href*="/receipts/"][href*="/print"]');
    const resources = performance.getEntriesByType('resource').map((entry) => entry.name);

    return {
        location: window.location.href,
        hasTable: Boolean(document.querySelector('.sales-table-card')),
        hasOwnTransaction: document.body.innerText.includes('STAGE13-BROWSER') ||
            document.querySelectorAll('.sales-table tbody tr').length >= 2,
        hasHistoryCss: resources.some((url) => url.includes('/assets/css/pages/sales-history.css')),
        hasHistoryJs: resources.some((url) => url.includes('/assets/js/pages/sales-history.js')),
        hasInternalCost: document.body.innerText.includes('Total HPP') ||
            document.body.innerText.includes('Laba kotor'),
        detailUrl: detailLink?.href || '',
        receiptUrl: receiptLink?.href || '',
        receiptTarget: receiptLink?.target || '',
        receiptRel: receiptLink?.rel || ''
    };
})()
'@
        returnByValue = $true
    }
    $historyResult = Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors
    $history = $historyResult.result.result.value

    Write-Output ('BROWSER_HISTORY_TABLE=' + $history.hasTable)
    Write-Output ('BROWSER_HISTORY_CSS=' + $history.hasHistoryCss)
    Write-Output ('BROWSER_HISTORY_JS=' + $history.hasHistoryJs)
    Write-Output ('BROWSER_HISTORY_INTERNAL_COST=' + $history.hasInternalCost)

    if (
        -not $history.hasTable -or
        -not $history.hasOwnTransaction -or
        -not $history.hasHistoryCss -or
        -not $history.hasHistoryJs -or
        $history.hasInternalCost -or
        -not $history.detailUrl -or
        -not $history.receiptUrl -or
        $history.receiptTarget -ne '_blank' -or
        $history.receiptRel -notmatch 'noopener'
    ) {
        throw 'Halaman riwayat transaksi Kasir tidak sesuai.'
    }

    $commandId++
    Send-CdpCommand -Socket $socket -Id $commandId -Method 'Page.navigate' -Params @{
        url = $history.detailUrl
    }
    [void] (Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors)
    Start-Sleep -Seconds 1

    $commandId++
    Send-CdpCommand -Socket $socket -Id $commandId -Method 'Runtime.evaluate' -Params @{
        expression = @'
({
    hasSnapshot: document.body.innerText.includes('Produk Browser Tahap 13'),
    hasPayment: document.body.innerText.includes('Ringkasan Pembayaran'),
    hasInternalCost: document.body.innerText.includes('Total HPP') ||
        document.body.innerText.includes('Laba kotor'),
    hasDetailCss: performance.getEntriesByType('resource')
        .some((entry) => entry.name.includes('/assets/css/pages/sale-detail.css'))
})
'@
        returnByValue = $true
    }
    $detailResult = Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors
    $detail = $detailResult.result.result.value

    Write-Output ('BROWSER_DETAIL_SNAPSHOT=' + $detail.hasSnapshot)
    Write-Output ('BROWSER_DETAIL_PAYMENT=' + $detail.hasPayment)
    Write-Output ('BROWSER_DETAIL_INTERNAL_COST=' + $detail.hasInternalCost)

    if (
        -not $detail.hasSnapshot -or
        -not $detail.hasPayment -or
        -not $detail.hasDetailCss -or
        $detail.hasInternalCost
    ) {
        throw 'Halaman detail transaksi Kasir tidak sesuai.'
    }

    $commandId++
    Send-CdpCommand -Socket $socket -Id $commandId -Method 'Page.addScriptToEvaluateOnNewDocument' -Params @{
        source = @'
window.print = function () {
    window.__stage15PrintCalls = (window.__stage15PrintCalls || 0) + 1;
    window.setTimeout(function () {
        window.dispatchEvent(new Event('afterprint'));
    }, 0);
};
'@
    }
    [void] (Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors)

    $previewUrl = $history.detailUrl + '/receipt'
    $commandId++
    Send-CdpCommand -Socket $socket -Id $commandId -Method 'Page.navigate' -Params @{
        url = $previewUrl
    }
    [void] (Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors)
    Start-Sleep -Seconds 1

    $commandId++
    Send-CdpCommand -Socket $socket -Id $commandId -Method 'Runtime.evaluate' -Params @{
        expression = @'
({
    hasSnapshot: document.body.innerText.includes('Produk Browser Tahap 13'),
    hasStage15Notice: document.body.innerText.includes(
        'Preview nota siap. Fitur cetak browser akan diaktifkan pada Tahap 15.'
    ),
    printCalls: window.__stage15PrintCalls || 0,
    hasInternalCost: document.body.innerText.includes('Total HPP') ||
        document.body.innerText.includes('Laba kotor')
})
'@
        returnByValue = $true
    }
    $previewResult = Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors
    $preview = $previewResult.result.result.value

    Write-Output ('BROWSER_PREVIEW_SNAPSHOT=' + $preview.hasSnapshot)
    Write-Output ('BROWSER_PREVIEW_AUTO_PRINT_CALLS=' + $preview.printCalls)

    if (
        -not $preview.hasSnapshot -or
        -not $preview.hasStage15Notice -or
        $preview.printCalls -ne 0 -or
        $preview.hasInternalCost
    ) {
        throw 'Preview Tahap 14 berubah atau menjalankan auto-print.'
    }

    $commandId++
    Send-CdpCommand -Socket $socket -Id $commandId -Method 'Page.navigate' -Params @{
        url = $history.receiptUrl
    }
    [void] (Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors)
    Start-Sleep -Seconds 2

    $commandId++
    Send-CdpCommand -Socket $socket -Id $commandId -Method 'Runtime.evaluate' -Params @{
        expression = @'
({
    hasPrintLayout: document.body.classList.contains('print-document'),
    hasReceipt: Boolean(document.querySelector('.receipt[data-receipt-auto-print="true"]')),
    hasSnapshot: document.body.innerText.includes('Produk Browser Tahap 13'),
    hasCopy: document.body.innerText.includes('SALINAN'),
    hasToolbar: Boolean(document.querySelector('.receipt-toolbar.print-hidden')),
    hasInternalCost: document.body.innerText.includes('Total HPP') ||
        document.body.innerText.includes('Laba kotor'),
    printCalls: window.__stage15PrintCalls || 0,
    paperWidth: document.querySelector('[data-receipt-paper-select]')?.value || '',
    hasReceiptCss: performance.getEntriesByType('resource')
        .some((entry) => entry.name.includes('/assets/css/print/receipt.css')),
    hasReceiptJs: performance.getEntriesByType('resource')
        .some((entry) => entry.name.includes('/assets/js/pages/receipt.js'))
})
'@
        returnByValue = $true
    }
    $receiptResult = Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors
    $receipt = $receiptResult.result.result.value

    Write-Output ('BROWSER_RECEIPT_PRINT_LAYOUT=' + $receipt.hasPrintLayout)
    Write-Output ('BROWSER_RECEIPT_SNAPSHOT=' + $receipt.hasSnapshot)
    Write-Output ('BROWSER_RECEIPT_COPY_LABEL=' + $receipt.hasCopy)
    Write-Output ('BROWSER_RECEIPT_AUTO_PRINT_CALLS=' + $receipt.printCalls)
    Write-Output ('BROWSER_RECEIPT_DEFAULT_WIDTH=' + $receipt.paperWidth)
    Write-Output ('BROWSER_RECEIPT_INTERNAL_COST=' + $receipt.hasInternalCost)

    if (
        -not $receipt.hasPrintLayout -or
        -not $receipt.hasReceipt -or
        -not $receipt.hasSnapshot -or
        -not $receipt.hasCopy -or
        -not $receipt.hasToolbar -or
        -not $receipt.hasReceiptCss -or
        -not $receipt.hasReceiptJs -or
        $receipt.hasInternalCost -or
        $receipt.printCalls -ne 1 -or
        $receipt.paperWidth -ne '80'
    ) {
        throw 'Halaman cetak struk tidak sesuai.'
    }

    $commandId++
    Send-CdpCommand -Socket $socket -Id $commandId -Method 'Runtime.evaluate' -Params @{
        expression = @'
(() => {
    const select = document.querySelector('[data-receipt-paper-select]');
    select.value = '58';
    select.dispatchEvent(new Event('change', { bubbles: true }));

    return {
        selected: select.value,
        classApplied: document.querySelector('[data-receipt-paper]').classList.contains('receipt-paper--58'),
        stored: window.localStorage.getItem('receipt_paper_width')
    };
})()
'@
        returnByValue = $true
    }
    $paper58Result = Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors
    $paper58 = $paper58Result.result.result.value

    if (
        $paper58.selected -ne '58' -or
        -not $paper58.classApplied -or
        $paper58.stored -ne '58'
    ) {
        throw 'Preferensi kertas 58 mm tidak tersimpan.'
    }

    $commandId++
    Send-CdpCommand -Socket $socket -Id $commandId -Method 'Page.reload'
    [void] (Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors)
    Start-Sleep -Seconds 2
    $commandId++
    Send-CdpCommand -Socket $socket -Id $commandId -Method 'Runtime.evaluate' -Params @{
        expression = @'
(() => {
    const select = document.querySelector('[data-receipt-paper-select]');
    const persisted58 = select.value === '58' &&
        document.querySelector('[data-receipt-paper]').classList.contains('receipt-paper--58');
    select.value = '80';
    select.dispatchEvent(new Event('change', { bubbles: true }));

    return {
        persisted58,
        stored80: window.localStorage.getItem('receipt_paper_width')
    };
})()
'@
        returnByValue = $true
    }
    $paper80Result = Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors
    $paper80 = $paper80Result.result.result.value

    if (-not $paper80.persisted58 -or $paper80.stored80 -ne '80') {
        throw 'Preferensi kertas tidak bertahan setelah reload.'
    }

    $commandId++
    Send-CdpCommand -Socket $socket -Id $commandId -Method 'Page.reload'
    [void] (Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors)
    Start-Sleep -Seconds 2
    $commandId++
    Send-CdpCommand -Socket $socket -Id $commandId -Method 'Runtime.evaluate' -Params @{
        expression = @'
(() => {
    const selected80 = document.querySelector('[data-receipt-paper-select]').value === '80';
    document.querySelector('[data-receipt-print-button]').click();

    return new Promise((resolve) => {
        window.setTimeout(() => resolve({
            selected80,
            printCalls: window.__stage15PrintCalls || 0,
            status: document.querySelector('[data-receipt-print-status]').textContent
        }), 50);
    });
})()
'@
        awaitPromise = $true
        returnByValue = $true
    }
    $manualPrintResult = Wait-CdpResponse -Socket $socket -CommandId $commandId -Errors $errors
    $manualPrint = $manualPrintResult.result.result.value

    Write-Output ('BROWSER_RECEIPT_58_PERSISTED=' + $paper80.persisted58)
    Write-Output ('BROWSER_RECEIPT_80_PERSISTED=' + $manualPrint.selected80)
    Write-Output ('BROWSER_RECEIPT_MANUAL_PRINT_CALLS=' + $manualPrint.printCalls)
    Write-Output ('BROWSER_RECEIPT_AFTERPRINT_STATUS=' + $manualPrint.status)
    Write-Output ('BROWSER_STAGE15_CONSOLE_ERROR_COUNT=' + $errors.Count)

    if (
        -not $manualPrint.selected80 -or
        $manualPrint.printCalls -ne 2 -or
        $manualPrint.status -ne 'Dialog cetak telah ditutup.' -or
        $errors.Count -ne 0
    ) {
        throw 'Print manual, afterprint, atau console browser tidak sesuai.'
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
