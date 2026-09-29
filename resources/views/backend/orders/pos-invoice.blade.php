@extends('backend.master-tailwind')

@section('title', __('Receipt #:id', ['id' => $order->id]))

@section('content')
    {{-- Ticket de caisse : apparence volontairement claire (noir sur blanc) et
         monospace, independante du theme clair/sombre, pour rester lisible sur
         papier. La largeur vient du reglage receiptMaxwidth. --}}
    <div id="printable-section"
        class="mx-auto border border-dotted border-black bg-white p-2 font-mono text-xs text-black"
        style="max-width: {{ $maxWidth }};">
        <div class="text-center">
            @if (readConfig('is_show_logo_invoice'))
                <img src="{{ assetImage(readconfig('site_logo')) }}" height="30" width="70"
                    alt="{{ __('Logo') }}" class="mx-auto">
            @endif
            @if (readConfig('is_show_site_invoice'))
                <h3 class="text-sm font-semibold">{{ readConfig('site_name') }}</h3>
            @endif
            @if (readConfig('is_show_address_invoice'))
                {{ readConfig('contact_address') }}<br>
            @endif
            @if (readConfig('is_show_phone_invoice'))
                {{ readConfig('contact_phone') }}<br>
            @endif
            @if (readConfig('is_show_email_invoice'))
                {{ readConfig('contact_email') }}<br>
            @endif
        </div>

        {{ __('User') }}: {{ auth()->user()->name }}<br>
        {{ __('Order') }}: #{{ $order->id }}<br>

        <hr class="my-1 border-0 border-t border-dashed border-black">

        <div class="flex flex-wrap justify-between gap-2">
            <div>
                @if (readConfig('is_show_customer_invoice'))
                    {{ __('Name') }}: {{ $order->customer->name ?? 'N/A' }}<br>
                    {{ __('Address') }}: {{ $order->customer->address ?? 'N/A' }}<br>
                    {{ __('Phone') }}: {{ $order->customer->phone ?? 'N/A' }}
                @endif
            </div>
            <div class="text-right">
                <p>{{ \Illuminate\Support\Carbon::now()->translatedFormat('d-M-Y') }}</p>
                <p>{{ date('h:i:s A') }}</p>
            </div>
        </div>

        <hr class="my-1 border-0 border-t border-dashed border-black">

        <table class="w-full">
            <thead>
                <tr>
                    <th class="text-left font-semibold">{{ __('Product') }}</th>
                    <th class="text-right font-semibold"></th>
                    <th class="text-right font-semibold">{{ __('Total') }} {{ currency()->symbol }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->products as $item)
                    <tr>
                        <td>{{ $item->product->name }}</td>
                        <td class="text-right">{{ $item->quantity }}*{{ $item->discounted_price }}</td>
                        <td class="text-right">{{ $item->total }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <hr class="my-1 border-0 border-t border-dashed border-black">

        <div class="summary">
            <table class="w-full">
                <tbody>
                    <tr>
                        <td>{{ __('Subtotal') }}:</td>
                        <td class="text-right">{{ number_format($order->sub_total, 2) }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('Discount') }}:</td>
                        <td class="text-right">{{ number_format($order->discount, 2) }}</td>
                    </tr>
                    <tr>
                        <td><strong>{{ __('Total') }}:</strong></td>
                        <td class="text-right"><strong>{{ number_format($order->total, 2) }}</strong></td>
                    </tr>
                    <tr>
                        <td>{{ __('Paid') }}:</td>
                        <td class="text-right">{{ number_format($order->paid + $order->change_amount, 2) }}</td>
                    </tr>
                    @if ($order->change_amount > 0)
                        <tr>
                            <td>{{ __('Change') }}:</td>
                            <td class="text-right">{{ number_format($order->change_amount, 2) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td>{{ __('Due') }}:</td>
                        <td class="text-right">{{ number_format($order->due, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <hr class="my-1 border-0 border-t border-dashed border-black">

        <div class="text-center">
            <p class="text-black/70">
                @if (readConfig('is_show_note_invoice'))
                    {{ readConfig('note_to_customer_invoice') }}
                @endif
            </p>
        </div>
    </div>

    <div class="mt-3 pb-3 text-center print:hidden">
        <button type="button" onclick="window.print()"
            class="inline-flex items-center gap-2 rounded-lg bg-qpos-brand px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
            <i class="fas fa-print" aria-hidden="true"></i>
            {{ __('Print') }}
        </button>
    </div>
@endsection

@push('style')
    <style>
        @media print {
            @page {
                margin-top: 5px;
                margin-left: 0;
                padding-left: 0;
            }
        }
    </style>
@endpush

@push('script')
    <script>
        // Une fois la boite d'impression fermee, le caissier revient a la caisse
        // pour la vente suivante. Le garde evite un double declenchement.
        var posUrl = "{{ route('backend.admin.cart.index') }}";
        var redirected = false;
        var goToPos = function() {
            if (redirected) return;
            redirected = true;
            window.location.href = posUrl;
        };
        window.addEventListener('afterprint', goToPos);
        window.print();
    </script>
@endpush
