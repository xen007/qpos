<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    /**
     * Save the language preference for the current browser session.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:fr,en'],
        ]);

        $request->session()->put('locale', $validated['locale']);

        $referer = $request->headers->get('referer');
        if ($referer !== null && $this->isSameOrigin($referer, $request)) {
            return redirect()->to($referer);
        }

        return redirect()->route('frontend.home');
    }

    private function isSameOrigin(string $url, Request $request): bool
    {
        $parts = parse_url($url);
        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        $urlPort = $parts['port'] ?? ($parts['scheme'] === 'https' ? 443 : 80);

        return strcasecmp($parts['scheme'], $request->getScheme()) === 0
            && strcasecmp($parts['host'], $request->getHost()) === 0
            && $urlPort === $request->getPort();
    }
}
