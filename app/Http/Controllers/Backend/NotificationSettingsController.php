<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\SystemSettings;
use App\Support\RoleAssignment;
use Illuminate\Http\Request;

final class NotificationSettingsController extends Controller
{
    private function authorizeSettings(Request $r): void { abort_unless(RoleAssignment::globalManager($r->user())&&$r->user()->can('system_monitor'),403); }
    public function index(Request $r){$this->authorizeSettings($r);return view('backend.notifications.settings');}
    public function update(Request $r,SystemSettings $settings)
    {
        $this->authorizeSettings($r);$v=$r->validate(['critical_threshold'=>'nullable|integer|min:1|max:999999999999','low_threshold'=>'required|integer|min:1|max:1000000']);
        $settings->update(['notification_critical_sale_threshold'=>isset($v['critical_threshold'])?(string)$v['critical_threshold']:null,'notification_low_stock_threshold'=>(string)$v['low_threshold']]);
        return back()->with('success',__('notifications.saved'));
    }
}
