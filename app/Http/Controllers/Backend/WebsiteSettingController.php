<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use App\Rules\ValidImageType;
use App\Http\Controllers\Controller;
use App\Services\SystemSettings;
use App\Trait\FileHandler;
use App\Support\SitePalette;
use Illuminate\Validation\Rule;

class WebsiteSettingController extends Controller
{
    public $fileHandler;

    public function __construct(FileHandler $fileHandler)
    {
        $this->fileHandler = $fileHandler;
    }

    public function websiteGeneral(Request $request)
    {
        return view('backend.settings.website-settings.general',['canManage'=>\App\Support\RoleAssignment::globalManager($request->user())]);
    }

    public function websiteInfoUpdate(Request $request)
    {
        $data=$request->validate([
            'site_name' => 'required|string|max:255',
            'site_url' => 'required|url:http,https|max:2048',
            'meta_description'=>'nullable|string|max:2000',
            'meta_keywords'=>'nullable|string|max:2000',
        ]);
        $this->save($request,$data);
        return to_route('backend.admin.settings.website.general', ['active-tab' => 'website-info'])
            ->with('success', __('Updated successfully'));
    }

    public function websiteContactsUpdate(Request $request)
    {
        $this->save($request,$request->validate([
            'contact_address'=>'nullable|string|max:1000', 'contact_phone'=>'nullable|string|max:80',
            'contact_fax'=>'nullable|string|max:80', 'contact_mobile'=>'nullable|string|max:80',
            'contact_email'=>'nullable|email|max:254', 'working_hour'=>'nullable|string|max:255',
        ]));
        return to_route('backend.admin.settings.website.general', ['active-tab' => 'contacts'])
            ->with('success', __('Updated successfully'));
    }

    public function websiteSocialLinkUpdate(Request $request)
    {
        $rules=[];
        foreach (['facebook','twitter','instagram','youtube','linkedin','pinterest','whatsapp','snapchat','tumblr'] as $network) $rules[$network.'_link']='nullable|url:http,https|max:2048';
        $this->save($request,$request->validate($rules));
        return to_route('backend.admin.settings.website.general', ['active-tab' => 'social-links'])
            ->with('success', __('Updated successfully'));
    }

    public function websiteStyleSettingsUpdate(Request $request)
    {
        abort_unless(\App\Support\RoleAssignment::globalManager($request->user()),403);
        abort_unless(\Illuminate\Support\Facades\Schema::hasTable('system_settings'),503,__('administration.migration_required'));
        $request->validate([
            'site_palette' => ['required', Rule::in(array_keys(SitePalette::options()))],
            'site_logo' => ['file', new ValidImageType, 'max:2048'],
            'favicon_icon' => ['file', new ValidImageType, 'max:2048'],
            'favicon_icon_apple' => ['file', new ValidImageType, 'max:2048'],
            'newsletter_subscribe'=>['required','boolean'],
        ]);
        $data=['site_palette'=>$request->input('site_palette'),'newsletter_subscribe'=>$request->boolean('newsletter_subscribe')];
        $oldFiles=[];$newFiles=[];

        if ($request->hasFile("site_logo")) {
            $site_logo = $this->fileHandler->uploadToPublic($request->file("site_logo"), "/assets/images/logo");
            $oldFiles[]=readConfig('site_logo');$newFiles[]=$site_logo;$data['site_logo']=$site_logo;
        }
        if ($request->hasFile("favicon_icon")) {
            $favicon_icon = $this->fileHandler->uploadToPublic($request->file("favicon_icon"), "/assets/images/logo");
            $oldFiles[]=readConfig('favicon_icon');$newFiles[]=$favicon_icon;$data['favicon_icon']=$favicon_icon;
        }
        if ($request->hasFile("favicon_icon_apple")) {
            $favicon_icon_apple = $this->fileHandler->uploadToPublic($request->file("favicon_icon_apple"), "/assets/images/logo");
            $oldFiles[]=readConfig('favicon_icon_apple');$newFiles[]=$favicon_icon_apple;$data['favicon_icon_apple']=$favicon_icon_apple;
        }
        try {$this->save($request,$data);} catch (\Throwable $e) {foreach($newFiles as $path)$this->fileHandler->securePublicUnlink($path);throw $e;}
        foreach($oldFiles as $path)if($path)$this->fileHandler->securePublicUnlink($path);
        return to_route('backend.admin.settings.website.general', ['active-tab' => 'style-settings'])
            ->with('success', __('Updated successfully'));
    }

    public function websiteCustomCssUpdate(Request $request)
    {
        $this->save($request,$request->validate(['custom_css'=>['nullable','string','max:50000','not_regex:/[<>]/']]));
        return to_route('backend.admin.settings.website.general', ['active-tab' => 'custom-css'])
            ->with('success', __('Updated successfully'));
    }

    public function websiteNotificationSettingsUpdate(Request $request)
    {
        $this->save($request,$request->validate([
            'notify_email_address'=>'nullable|email|max:254',
            'notify_messages_status'=>'required|boolean','notify_comments_status'=>'required|boolean',
        ]));
        return to_route('backend.admin.settings.website.general', ['active-tab' => 'notification-settings'])
            ->with('success', __('Updated successfully'));
    }
    

    public function websiteStatusUpdate(Request $request)
    {
        $request->validate([
            'is_live' => 'required|in:0,1',
            'close_msg' => 'nullable|string|max:2000',
        ]);
        $this->save($request,['is_live'=>$request->boolean('is_live'),'close_msg'=>(string)$request->input('close_msg','')]);
        return to_route('backend.admin.settings.website.general', ['active-tab' => 'website-status'])
            ->with('success', __('Updated successfully'));
    }
    public function websiteInvoiceUpdate(Request $request)
    {
        $rules=['note_to_customer_invoice'=>'nullable|string|max:2000','receiptMaxwidth'=>'required|in:300px,400px,500px'];
        foreach(['logo','site','phone','email','address','customer','note'] as $field)$rules['is_show_'.$field.'_invoice']='required|boolean';
        $this->save($request,$request->validate($rules));
        return to_route('backend.admin.settings.website.general', ['active-tab' => 'invoice-settings'])
            ->with('success', __('Updated successfully'));
    }

    private function save(Request $request,array $data): void
    {
        abort_unless(\App\Support\RoleAssignment::globalManager($request->user()),403);
        app(SystemSettings::class)->update($data);
    }
}
