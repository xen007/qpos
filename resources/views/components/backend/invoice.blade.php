{{--
    Facture imprimable (skin Tailwind).

    Reprend la structure des factures AdminLTE (en-tete, coordonnees, articles,
    note et totaux) sans dependre du CSS `.invoice` d'AdminLTE, absent des pages
    migrees. Les deux pages de facture la partagent ; la facture d'encaissement
    complete les blocs via les slots `information` et `totals`.

    La coquille de navigation porte `print:hidden` : seule la facture s'imprime.
--}}
@props(['title', 'order'])

<div data-qpos-invoice class="mx-auto w-full max-w-4xl space-y-6 bg-qpos-surface p-6 text-qpos-ink">
    {{-- En-tete --}}
    <header class="flex flex-wrap items-start justify-between gap-4">
        <h1 class="flex items-center gap-2 text-xl font-semibold">
            @if (readConfig('is_show_logo_invoice'))
                <img src="{{ assetImage(readconfig('site_logo')) }}" alt="{{ __('Logo') }}" width="40"
                    height="40" class="rounded-full">
            @endif
            @if (readConfig('is_show_site_invoice'))
                {{ readConfig('site_name') }}
            @endif
        </h1>

        <h2 class="text-lg font-semibold">{{ $title }}</h2>

        <p class="text-sm text-qpos-muted">{{ __('Date') }}: {{ date('d/m/Y') }}</p>
    </header>

    {{-- Coordonnees --}}
    <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-3">
        <div>
            @if (readConfig('is_show_customer_invoice'))
                <p class="font-medium">{{ __('To') }}</p>
                <p><strong>{{ __('Name') }}: {{ $order->customer->name ?? 'N/A' }}</strong></p>
                <p>{{ __('Address') }}: {{ $order->customer->address ?? 'N/A' }}</p>
                <p>{{ __('Phone') }}: {{ $order->customer->phone ?? 'N/A' }}</p>
            @endif
        </div>

        <div>
            <p class="font-medium">{{ __('From') }}</p>
            @if (readConfig('is_show_site_invoice'))
                <p><strong>{{ __('Name') }}: {{ readConfig('site_name') }}</strong></p>
            @endif
            @if (readConfig('is_show_address_invoice'))
                <p>{{ __('Address') }}: {{ readConfig('contact_address') }}</p>
            @endif
            @if (readConfig('is_show_phone_invoice'))
                <p>{{ __('Phone') }}: {{ readConfig('contact_phone') }}</p>
            @endif
            @if (readConfig('is_show_email_invoice'))
                <p>{{ __('Email') }}: {{ readConfig('contact_email') }}</p>
            @endif
        </div>

        <div>
            <p class="font-medium">{{ __('Information') }}</p>
            {{ $information ?? '' }}
        </div>
    </div>

    {{-- Articles --}}
    <table class="w-full border-collapse text-sm">
        <thead>
            <tr class="border-b border-qpos-line text-left">
                <th class="py-2 font-semibold">{{ __('No.') }}</th>
                <th class="py-2 font-semibold">{{ __('Product') }}</th>
                <th class="py-2 font-semibold">{{ __('Quantity') }}</th>
                <th class="py-2 font-semibold">{{ __('Price') }} {{ currency()->symbol ?? '' }}</th>
                <th class="py-2 text-right font-semibold">{{ __('Subtotal') }} {{ currency()->symbol ?? '' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->products as $item)
                <tr class="border-b border-qpos-line/60">
                    <td class="py-2">{{ $loop->index + 1 }}</td>
                    <td class="py-2">{{ $item->product->name }}</td>
                    <td class="py-2">{{ $item->quantity }} {{ optional($item->product->unit)->short_name }}</td>
                    <td class="py-2">
                        {{ number_format($item->discounted_price, 2, '.', ',') }}
                        @if ($item->price > $item->discounted_price)
                            <br><del class="text-qpos-muted">{{ number_format($item->price, 2, '.', ',') }}</del>
                        @endif
                    </td>
                    <td class="py-2 text-right tabular-nums">{{ number_format($item->total, 2, '.', ',') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Note et totaux --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <p class="text-sm text-qpos-muted">
            @if (readConfig('is_show_note_invoice'))
                {{ readConfig('note_to_customer_invoice') }}
            @endif
        </p>

        <table class="w-full text-sm">
            <tbody>
                {{ $totals ?? '' }}
            </tbody>
        </table>
    </div>
</div>
