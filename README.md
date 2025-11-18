# Laravel Payment Gateways by Aldi

A comprehensive Laravel package for integrating multiple payment gateways with unified API and multi-account support.

## Supported Payment Gateways

| Gateway | Status | Region |
|---------|--------|--------|
| **SenangPay** | ✅ Ready | Malaysia |
| **iPay88** | 🚧 Coming Soon |  |
| **PayPal** | 🚧 Coming Soon |  |
| **Billplz** | 🚧 Coming Soon |  |
| **Midtrans** | 🚧 Coming Soon |  |

## Features

✅ **Unified API** - Same interface for all payment gateways  
✅ **Exception-Based** - Clean error handling with specific exceptions  
✅ **Multi-Account** - Support multiple merchant accounts per gateway  
✅ **Type Safety** - Full PHP type hints and return types  
✅ **Laravel Integration** - Service provider, facades, and config publishing  
✅ **Sandbox Mode** - Easy testing with sandbox environments  
✅ **Logging** - Built-in logging for debugging and monitoring  
✅ **Extensible** - Easy to add new payment gateways  

## Requirements

- PHP 8.1 or higher
- Laravel 10.x or higher

## Installation

Install the package via Composer:

```bash
composer require aldiazhar/laravel-payment-gateways
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=payment-gateways-config
```

Add your payment gateway credentials to `.env`:

```env
# SenangPay Configuration
SENANGPAY_MERCHANT_ID=your_merchant_id
SENANGPAY_SECRET_KEY=your_secret_key
SENANGPAY_SANDBOX=true

# Optional: Multiple SenangPay Accounts
SENANGPAY_SECONDARY_MERCHANT_ID=secondary_merchant_id
SENANGPAY_SECONDARY_SECRET_KEY=secondary_secret_key
SENANGPAY_SECONDARY_SANDBOX=true
```

## Quick Start

### 1. Basic Payment Flow

```php
use Aldiazhar\PaymentGateways\Facades\SenangPay;

// Prepare payment data
$payload = [
    'description' => 'Order #12345',
    'amount' => '50.00',
    'order_id' => 'INV-12345',
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com',
    'customer_phone' => '60123456789',
];

// Get payment form inputs
$inputs = SenangPay::inputs($payload);

// Get payment gateway URL
$paymentUrl = SenangPay::url();

// Render payment form
return view('payment.form', compact('inputs', 'paymentUrl'));
```

### 2. Payment Form (Blade Template)

```html
<form method="POST" action="{{ $paymentUrl }}">
    <input type="hidden" name="detail" value="{{ $inputs['detail'] }}">
    <input type="hidden" name="amount" value="{{ $inputs['amount'] }}">
    <input type="hidden" name="order_id" value="{{ $inputs['order_id'] }}">
    <input type="hidden" name="name" value="{{ $inputs['name'] }}">
    <input type="hidden" name="email" value="{{ $inputs['email'] }}">
    <input type="hidden" name="phone" value="{{ $inputs['phone'] }}">
    <input type="hidden" name="hash" value="{{ $inputs['hash'] }}">
    
    <button type="submit">Proceed to Payment</button>
</form>
```

### 3. Handle Payment Return/Callback

```php
use Aldiazhar\PaymentGateways\Facades\SenangPay;
use Aldiazhar\PaymentGateways\Exceptions\InvalidHashException;
use Aldiazhar\PaymentGateways\Exceptions\PaymentFailedException;

public function return(Request $request)
{
    try {
        // Verify signature and ensure payment success in one call
        SenangPay::validatePayment($request->all());
        
        // Payment is verified and successful!
        $invoice = Invoice::where('invoice_no', $request->order_id)->first();
        $invoice->markAsPaid();
        
        return redirect()->route('payment.success');
        
    } catch (InvalidHashException $e) {
        // Invalid signature - possible tampering
        Log::warning('Invalid payment hash', ['error' => $e->getMessage()]);
        return redirect()->route('payment.failed')
            ->with('error', 'Payment verification failed');
            
    } catch (PaymentFailedException $e) {
        // Payment failed or cancelled
        Log::info('Payment failed', ['error' => $e->getMessage()]);
        return redirect()->route('payment.failed')
            ->with('error', $e->getMessage());
    }
}
```

## Advanced Usage

### Multiple Accounts

Switch between different merchant accounts:

```php
// Use secondary account
$inputs = SenangPay::account('secondary')->inputs($payload);
$url = SenangPay::account('secondary')->url();

// Use tertiary account
$inputs = SenangPay::account('tertiary')->inputs($payload);

// Switch back to default account
$inputs = SenangPay::account()->inputs($payload);
```

### Separate Verification Steps

```php
// Option 1: Verify hash only (throws exception)
try {
    SenangPay::verifyOrFail($request->all());
    // Hash is valid
} catch (InvalidHashException $e) {
    // Invalid hash
}

// Option 2: Verify hash only (returns boolean)
if (SenangPay::verify($request->all())) {
    // Hash is valid
}

// Option 3: Ensure payment success (throws exception)
try {
    SenangPay::ensureSuccess($request->all());
    // Payment is successful
} catch (PaymentFailedException $e) {
    // Payment failed
}

// Option 4: Verify both hash and success (recommended)
SenangPay::validatePayment($request->all()); // Throws exceptions if fails
```

### Check Transaction Status

```php
$result = SenangPay::check('INV-12345');

if ($result['status'] === 1) {
    // Payment successful
    $transactionDetails = $result['data'];
    echo "Transaction ID: " . $transactionDetails['payment_info']['transaction_id'];
} else {
    // Payment pending or failed
    echo $result['message'];
}
```

### Get Gateway Information

```php
// Get gateway name
$name = SenangPay::getName(); // Returns: "SenangPay"

// Get current configuration
$config = SenangPay::getConfig();
// Returns: ['merchant_id' => '...', 'secret' => '...', 'sandbox' => true]

// Get current account name
$account = SenangPay::getCurrentAccount(); // Returns: "default" or "secondary"
```

## Controller Example

Complete controller implementation:

```php
<?php

namespace App\Http\Controllers\Payment;

use Aldiazhar\PaymentGateways\Exceptions\InvalidHashException;
use Aldiazhar\PaymentGateways\Exceptions\PaymentFailedException;
use Aldiazhar\PaymentGateways\Facades\SenangPay;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Show payment form
     */
    public function show(Invoice $invoice)
    {
        $payload = [
            'description' => "Invoice #{$invoice->invoice_no}",
            'amount' => number_format($invoice->amount, 2, '.', ''),
            'order_id' => $invoice->invoice_no,
            'customer_name' => $invoice->customer->name,
            'customer_email' => $invoice->customer->email,
            'customer_phone' => $invoice->customer->phone,
        ];

        // Use specific account if configured
        $config = $invoice->getPaymentConfig();
        $senangpay = SenangPay::account($config['account'] ?? null);

        return view('payment.form', [
            'inputs' => $senangpay->inputs($payload),
            'url' => $senangpay->url(),
            'invoice' => $invoice,
        ]);
    }

    /**
     * Handle payment return (user redirect)
     */
    public function return(Request $request)
    {
        $invoice = Invoice::where('invoice_no', $request->order_id)->firstOrFail();
        $invoice->markAsPending();

        $config = $invoice->getPaymentConfig();
        $senangpay = SenangPay::account($config['account'] ?? null);

        try {
            $senangpay->validatePayment($request->all());

            // Payment successful
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_data' => $request->all(),
            ]);

            // Process post-payment actions
            $invoice->processAfterPaid();

            Log::info('Payment successful', [
                'gateway' => 'SenangPay',
                'order_id' => $request->order_id,
                'transaction_id' => $request->transaction_id,
            ]);

            return redirect()->route('payment.success', $invoice)
                ->with('success', 'Payment successful!');

        } catch (InvalidHashException $e) {
            $invoice->update(['status' => 'failed']);
            
            Log::warning('Payment verification failed', [
                'gateway' => 'SenangPay',
                'order_id' => $request->order_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('payment.failed', $invoice)
                ->with('error', 'Payment verification failed. Please contact support.');

        } catch (PaymentFailedException $e) {
            $invoice->update(['status' => 'failed']);
            
            Log::info('Payment failed', [
                'gateway' => 'SenangPay',
                'order_id' => $request->order_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('payment.failed', $invoice)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Handle payment callback (backend notification)
     */
    public function callback(Request $request)
    {
        Log::info('Payment callback received', [
            'gateway' => 'SenangPay',
            'order_id' => $request->order_id,
            'status_id' => $request->status_id,
        ]);

        $invoice = Invoice::where('invoice_no', $request->order_id)->firstOrFail();
        $invoice->markAsPending();

        $config = $invoice->getPaymentConfig();
        $senangpay = SenangPay::account($config['account'] ?? null);

        try {
            $senangpay->validatePayment($request->all());

            // Payment successful
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_data' => $request->all(),
            ]);

            $invoice->processAfterPaid();

            Log::info('Payment callback confirmed', [
                'gateway' => 'SenangPay',
                'order_id' => $request->order_id,
            ]);

            return response('OK', 200);

        } catch (InvalidHashException $e) {
            Log::error('Callback verification failed', [
                'gateway' => 'SenangPay',
                'order_id' => $request->order_id,
            ]);

            return response('Invalid hash', 400);

        } catch (PaymentFailedException $e) {
            $invoice->update(['status' => 'failed']);
            
            Log::info('Callback payment failed', [
                'gateway' => 'SenangPay',
                'order_id' => $request->order_id,
            ]);

            return response('OK', 200);
        }
    }
}
```

## API Reference

### PaymentGatewayInterface

All payment gateways implement this interface:

```php
interface PaymentGatewayInterface
{
    // Prepare payment form inputs
    public function inputs(array $payload): array;
    
    // Get payment gateway URL
    public function url(): string;
    
    // Check transaction status
    public function check(string $orderId): array;
    
    // Verify payment signature (returns boolean)
    public function verify(array $data): bool;
    
    // Verify payment signature (throws exception)
    public function verifyOrFail(array $data): void;
    
    // Ensure payment is successful (throws exception)
    public function ensureSuccess(array $data): void;
    
    // Verify signature and ensure success in one call
    public function validatePayment(array $data): void;
    
    // Switch to specific account
    public function account(?string $account = null): self;
    
    // Get gateway name
    public function getName(): string;
    
    // Get current configuration
    public function getConfig(): array;
    
    // Get current account name
    public function getCurrentAccount(): string;
}
```

### Exceptions

```php
// Thrown when payment signature/hash is invalid
Aldiazhar\PaymentGateways\Exceptions\InvalidHashException

// Thrown when payment status is not successful
Aldiazhar\PaymentGateways\Exceptions\PaymentFailedException
```

## Testing

The package includes sandbox mode for testing:

```env
SENANGPAY_SANDBOX=true
```

When sandbox is enabled, all API calls will use the sandbox endpoints.

## Contributing

Contributions are welcome! To add a new payment gateway:

1. Create a new folder under `src/` (e.g., `src/IPay88/`)
2. Implement `PaymentGatewayInterface`
3. Register the service in `PaymentGatewaysServiceProvider`
4. Create a Facade in `src/Facades/`
5. Add configuration in `config/payment-gateways.php`
6. Update the README
7. Submit a pull request

## Security

If you discover any security issues, please email permana.azhar.aldi@gmail.com instead of using the issue tracker.

## License

This package is open-source software licensed under the [MIT license](LICENSE).

## Credits

- **Aldi** - Package Author
- All Contributors

## Support

- 📧 Email: permana.azhar.aldi@gmail.com
- 🐛 Issues: [GitHub Issues](https://github.com/aldi/laravel-payment-gateways/issues)
- 📖 Documentation: [GitHub Wiki](https://github.com/aldi/laravel-payment-gateways/wiki)

---

Made with ❤️ by Aldi