@extends('backend.master-tailwind')

@section('title', __('General Settings'))

@section('content')
    @php
        // Onglets : la cle est reprise du parametre ?active-tab=, exactement les
        // valeurs qu'utilisent les redirections du controleur apres enregistrement.
        $tabs = [
            ['key' => 'website-info', 'icon' => 'fas fa-desktop', 'label' => __('Website Info'), 'permission' => 'website_settings'],
            ['key' => 'contacts', 'icon' => 'fas fa-address-book', 'label' => __('Contacts'), 'permission' => 'contact_settings'],
            ['key' => 'social-links', 'icon' => 'fas fa-share-alt', 'label' => __('Social Links'), 'permission' => 'socials_settings'],
            ['key' => 'style-settings', 'icon' => 'fas fa-swatchbook', 'label' => __('Style Settings'), 'permission' => 'style_settings'],
            ['key' => 'custom-css', 'icon' => 'fas fa-code', 'label' => __('Custom CSS'), 'permission' => 'custom_settings'],
            ['key' => 'notification-settings', 'icon' => 'fas fa-envelope', 'label' => __('Notification Settings'), 'permission' => 'notification_settings'],
            ['key' => 'website-status', 'icon' => 'fas fa-power-off', 'label' => __('Website Status'), 'permission' => 'website_status_settings'],
            ['key' => 'invoice-settings', 'icon' => 'fas fa-file-invoice', 'label' => __('Invoice Settings'), 'permission' => 'invoice_settings'],
        ];
    @endphp

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[240px_1fr]" data-qpos-tabs>
        <nav class="flex flex-wrap gap-2 lg:flex-col lg:flex-nowrap" role="tablist" aria-orientation="vertical">
            @foreach ($tabs as $tab)
                @can($tab['permission'])
                    <button type="button" role="tab" data-qpos-tab="{{ $tab['key'] }}" aria-selected="false"
                        class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-medium text-qpos-muted transition hover:bg-qpos-page">
                        <x-backend.icon :name="$tab['icon']" />
                        {{ $tab['label'] }}
                    </button>
                @endcan
            @endforeach
        </nav>

        <div class="space-y-6">
            {{-- 1. Informations du site --}}
            @can('website_settings')
                <div data-qpos-tab-panel="website-info" class="hidden">
                    <x-backend.card :title="__('Website Info')">
                        <form action="{{ route('backend.admin.settings.website.info.update') }}" method="post">
                            @csrf

                            <div class="grid grid-cols-1 gap-5">
                                <x-backend.input name="site_name" :label="__('Website Title')" :value="readConfig('site_name')"
                                    :placeholder="__('Enter Site Title')" />

                                <x-backend.textarea name="meta_description" :label="__('Meta Description')"
                                    :value="readConfig('meta_description')" :placeholder="__('Enter Meta Description')"
                                    rows="2" />

                                <x-backend.textarea name="meta_keywords" :label="__('Meta Keywords')"
                                    :value="readConfig('meta_keywords')" :placeholder="__('Enter Keywords')" rows="2" />

                                <x-backend.input name="site_url" type="url" :label="__('Website URL')"
                                    :value="readConfig('site_url')" :placeholder="__('Enter Site URL')" />
                            </div>

                            <div class="mt-6">
                                <x-backend.button icon="fas fa-reply">{{ __('Save changes') }}</x-backend.button>
                            </div>
                        </form>
                    </x-backend.card>
                </div>
            @endcan

            {{-- 2. Coordonnees --}}
            @can('contact_settings')
                <div data-qpos-tab-panel="contacts" class="hidden">
                    <x-backend.card :title="__('Contacts')">
                        <form action="{{ route('backend.admin.settings.website.contacts.update') }}" method="post">
                            @csrf

                            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                                <x-backend.input name="contact_address" :label="__('Address')"
                                    :value="readConfig('contact_address')" />

                                <x-backend.input name="contact_phone" type="tel" :label="__('Phone')"
                                    :value="readConfig('contact_phone')" :placeholder="__('Phone')" />

                                <x-backend.input name="contact_fax" type="tel" :label="__('Fax')"
                                    :value="readConfig('contact_fax')" :placeholder="__('Fax')" />

                                <x-backend.input name="contact_mobile" type="tel" :label="__('Mobile')"
                                    :value="readConfig('contact_mobile')" :placeholder="__('Mobile')" />

                                <x-backend.input name="contact_email" type="email" :label="__('Email')"
                                    :value="readConfig('contact_email')" :placeholder="__('Email')" />

                                <x-backend.input name="working_hour" :label="__('Working Time')"
                                    :value="readConfig('working_hour')"
                                    :placeholder="__('Sunday to Thursday, 8:00 AM to 5:00 PM')" />
                            </div>

                            <div class="mt-6">
                                <x-backend.button icon="fas fa-reply">{{ __('Save changes') }}</x-backend.button>
                            </div>
                        </form>
                    </x-backend.card>
                </div>
            @endcan

            {{-- 3. Reseaux sociaux --}}
            @can('socials_settings')
                <div data-qpos-tab-panel="social-links" class="hidden">
                    <x-backend.card :title="__('Social Links')">
                        <form action="{{ route('backend.admin.settings.website.social.link.update') }}" method="post">
                            @csrf

                            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                                <x-backend.input name="facebook_link" type="url" icon="fab fa-facebook"
                                    label="Facebook" :value="readConfig('facebook_link')" placeholder="Facebook" />

                                <x-backend.input name="twitter_link" type="url" icon="fab fa-twitter" label="Twitter"
                                    :value="readConfig('twitter_link')" placeholder="Twitter" />

                                <x-backend.input name="linkedin_link" type="url" icon="fab fa-linkedin"
                                    label="Linkedin" :value="readConfig('linkedin_link')" placeholder="Linkedin" />

                                <x-backend.input name="youtube_link" type="url" icon="fab fa-youtube" label="Youtube"
                                    :value="readConfig('youtube_link')" placeholder="Youtube" />

                                <x-backend.input name="instagram_link" type="url" icon="fab fa-instagram"
                                    label="Instagram" :value="readConfig('instagram_link')" placeholder="Instagram" />

                                <x-backend.input name="pinterest_link" type="url" icon="fab fa-pinterest"
                                    label="Pinterest" :value="readConfig('pinterest_link')" placeholder="Pinterest" />

                                <x-backend.input name="tumblr_link" type="url" icon="fab fa-tumblr" label="Tumblr"
                                    :value="readConfig('tumblr_link')" placeholder="Tumblr" />

                                <x-backend.input name="snapchat_link" type="url" icon="fab fa-snapchat"
                                    label="Snapchat" :value="readConfig('snapchat_link')" placeholder="Snapchat" />

                                <x-backend.input name="whatsapp_link" type="url" icon="fab fa-whatsapp"
                                    label="Whatsapp" :value="readConfig('whatsapp_link')" placeholder="Whatsapp" />
                            </div>

                            <div class="mt-6">
                                <x-backend.button icon="fas fa-reply">{{ __('Save changes') }}</x-backend.button>
                            </div>
                        </form>
                    </x-backend.card>
                </div>
            @endcan

            {{-- 4. Style (logo, favicon, icone Apple, newsletter) --}}
            @can('style_settings')
                <div data-qpos-tab-panel="style-settings" class="hidden">
                    <x-backend.card :title="__('Style Settings')">
                        <form action="{{ route('backend.admin.settings.website.style.settings.update') }}" method="post"
                            enctype="multipart/form-data">
                            @csrf

                            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                <fieldset class="lg:col-span-2 m-0 min-w-0 border-0 p-0">
                                    <legend class="mb-2 text-sm font-semibold text-qpos-ink">{{ __('Site palette') }}</legend>
                                    <p class="mb-4 text-sm text-qpos-muted">{{ __('The palette applies to the whole site. Light and dark mode remain personal.') }}</p>
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                        @foreach (\App\Support\SitePalette::options() as $key => $palette)
                                            <label class="qpos-palette-option rounded-xl border border-qpos-line p-4 cursor-pointer">
                                                <span class="flex items-center gap-2 text-sm font-semibold">
                                                    <input type="radio" name="site_palette" value="{{ $key }}" required
                                                        @checked(old('site_palette', \App\Support\SitePalette::current()) === $key)>
                                                    {{ $palette['label'] }}
                                                </span>
                                                <span class="mt-3 flex gap-2" aria-hidden="true">
                                                    <span class="h-8 flex-1 rounded-md" style="background: {{ $palette['color'] }}"></span>
                                                    <span class="h-8 w-8 rounded-md" style="background: #88B04B"></span>
                                                </span>
                                                <span class="mt-2 block text-xs text-qpos-muted">{{ $palette['color'] }} + #88B04B</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('site_palette') <p class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
                                </fieldset>
                                <div class="lg:col-span-2">
                                    <x-backend.image-field name="site_logo" field-id="siteLogo" :label="__('Site') . ' ' . __('Logo')"
                                        :current-image-url="assetImage(readconfig('site_logo'))" />
                                    <p class="mt-2 text-xs text-qpos-muted">
                                        <x-backend.icon name="far fa-question-circle" />
                                        {{ __('( :size px ) - Extensions: .png, .jpg, .jpeg, .gif, .bmp, .webp', ['size' => '260x60']) }}
                                    </p>
                                </div>

                                <div>
                                    <x-backend.image-field name="favicon_icon" field-id="faviconIcon" :label="__('Favicon')"
                                        :current-image-url="assetImage(readconfig('favicon_icon'))" />
                                    <p class="mt-2 text-xs text-qpos-muted">
                                        <x-backend.icon name="far fa-question-circle" />
                                        {{ __('( :size px ) - Extensions: .png, .jpg, .jpeg, .gif, .bmp, .webp', ['size' => '32x32']) }}
                                    </p>
                                </div>

                                <div>
                                    <x-backend.image-field name="favicon_icon_apple" field-id="faviconApple"
                                        :label="__('Apple Icon')"
                                        :current-image-url="assetImage(readconfig('favicon_icon_apple'))" />
                                    <p class="mt-2 text-xs text-qpos-muted">
                                        <x-backend.icon name="far fa-question-circle" />
                                        {{ __('( :size px ) - Extensions: .png, .jpg, .jpeg, .gif, .bmp, .webp', ['size' => '180x180']) }}
                                    </p>
                                </div>

                                <div class="lg:col-span-2">
                                    <x-backend.radio-group name="newsletter_subscribe" :label="__('Newsletter Subscribe')" :options="[
                                        1 => __('Active'),
                                        0 => __('Not Active'),
                                    ]" :selected="readConfig('newsletter_subscribe')" />
                                </div>
                            </div>

                            <div class="mt-6">
                                <x-backend.button icon="fas fa-reply">{{ __('Save changes') }}</x-backend.button>
                            </div>
                        </form>
                    </x-backend.card>
                </div>
            @endcan

            {{-- 5. CSS personnalise --}}
            @can('custom_settings')
                <div data-qpos-tab-panel="custom-css" class="hidden">
                    <x-backend.card :title="__('Custom CSS')">
                        <form action="{{ route('backend.admin.settings.website.custom.css.update') }}" method="post">
                            @csrf

                            <x-backend.textarea name="custom_css" :value="readConfig('custom_css')" rows="17" />

                            <div class="mt-6">
                                <x-backend.button icon="fas fa-reply">{{ __('Save changes') }}</x-backend.button>
                            </div>
                        </form>
                    </x-backend.card>
                </div>
            @endcan

            {{-- 6. Notifications --}}
            @can('notification_settings')
                <div data-qpos-tab-panel="notification-settings" class="hidden">
                    <x-backend.card :title="__('Notification Settings')">
                        <form action="{{ route('backend.admin.settings.website.notification.settings.update') }}"
                            method="post">
                            @csrf

                            <div class="grid grid-cols-1 gap-5">
                                <x-backend.input name="notify_email_address" type="email"
                                    :label="__('Website Notification Email')" :value="readConfig('notify_email_address')"
                                    :placeholder="__('Enter email')" />

                                <x-backend.radio-group name="notify_messages_status"
                                    :label="__('Send me an email on new contact messages')" :options="[
                                        1 => __('Yes'),
                                        0 => __('No'),
                                    ]" :selected="readConfig('notify_messages_status')" />

                                <x-backend.radio-group name="notify_comments_status"
                                    :label="__('Send me an email on new comments')" :options="[
                                        1 => __('Yes'),
                                        0 => __('No'),
                                    ]" :selected="readConfig('notify_comments_status')" />
                            </div>

                            <div class="mt-6">
                                <x-backend.button icon="fas fa-reply">{{ __('Save changes') }}</x-backend.button>
                            </div>
                        </form>
                    </x-backend.card>
                </div>
            @endcan

            {{-- 7. Etat du site --}}
            @can('website_status_settings')
                <div data-qpos-tab-panel="website-status" class="hidden">
                    <x-backend.card :title="__('Website Status')">
                        <form action="{{ route('backend.admin.settings.website.status.update') }}" method="post">
                            @csrf

                            <div class="grid grid-cols-1 gap-5">
                                <x-backend.radio-group name="is_live" :label="__('Website Status')" :options="[
                                    1 => __('Active'),
                                    0 => __('Not Active'),
                                ]" :selected="readConfig('is_live')" />

                                {{-- Visible seulement si le site est hors ligne (script en bas de page). --}}
                                <div data-qpos-close-message-panel
                                    class="{{ readConfig('is_live') == 1 ? 'hidden' : '' }}">
                                    <x-backend.textarea name="close_msg" :label="__('Close Message')"
                                        :value="readConfig('close_msg')" :placeholder="__('Close Message')" rows="4" />
                                    {{-- Le controleur websiteStatusUpdate() n'enregistre que is_live :
                                         ce message n'est donc pas conserve (voir la documentation). --}}
                                </div>
                            </div>

                            <div class="mt-6">
                                <x-backend.button icon="fas fa-reply">{{ __('Save changes') }}</x-backend.button>
                            </div>
                        </form>
                    </x-backend.card>
                </div>
            @endcan

            {{-- 8. Facture --}}
            @can('invoice_settings')
                <div data-qpos-tab-panel="invoice-settings" class="hidden">
                    <x-backend.card :title="__('Invoice Settings')">
                        <form action="{{ route('backend.admin.settings.website.invoice.update') }}" method="post">
                            @csrf

                            <x-backend.input name="note_to_customer_invoice" :label="__('Note to customer')"
                                :value="readConfig('note_to_customer_invoice')"
                                :placeholder="__('Enter message for invoice')" />

                            <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
                                @foreach ([
                                    'is_show_logo_invoice' => __('Logo'),
                                    'is_show_site_invoice' => __('Site Name'),
                                    'is_show_phone_invoice' => __('Phone'),
                                    'is_show_email_invoice' => __('Email'),
                                    'is_show_address_invoice' => __('Address'),
                                    'is_show_customer_invoice' => __('Customer'),
                                    'is_show_note_invoice' => __('Note to customer'),
                                ] as $field => $label)
                                    <x-backend.switch :name="$field" :label="$label"
                                        :checked="readConfig($field) == 1" />
                                @endforeach
                            </div>

                            <div class="mt-5 lg:w-1/2">
                                <x-backend.select name="receiptMaxwidth" :label="__('POS Invoice Width')" :options="[
                                    '300px' => __('Small'),
                                    '400px' => __('Medium'),
                                    '500px' => __('Large'),
                                ]" :selected="readConfig('receiptMaxwidth')" />
                            </div>

                            <div class="mt-6">
                                <x-backend.button icon="fas fa-reply">{{ __('Save changes') }}</x-backend.button>
                            </div>
                        </form>
                    </x-backend.card>
                </div>
            @endcan
        </div>
    </div>
@endsection

@push('script')
    <script>
        // Le message de fermeture ne sert que si le site est hors ligne.
        (() => {
            const panel = document.querySelector('[data-qpos-close-message-panel]');
            const radios = document.querySelectorAll('input[name="is_live"]');

            if (!panel || radios.length === 0) {
                return;
            }

            const sync = () => {
                const offline = Array.from(radios).some(
                    (radio) => radio.checked && radio.value === '0'
                );

                panel.classList.toggle('hidden', !offline);
            };

            radios.forEach((radio) => radio.addEventListener('change', sync));
            sync();
        })();
    </script>
@endpush
