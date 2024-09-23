<?php

namespace App\Http\Controllers\Eventmie;

use File;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

use App\Http\Controllers\Controller;
use Classiebit\Eventmie\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use Intervention\Image\Facades\Image;
use Facades\Classiebit\Eventmie\Eventmie;

use Classiebit\Eventmie\Notifications\MailNotification;
use Classiebit\Eventmie\Http\Controllers\ProfileController as BaseProfileController;

class ProfileController extends BaseProfileController
{    
    /**
     * index
     *
     * @param  String $view
     * @param  Array $extra
     * @return view
     */
    public function index($view = 'vendor.eventmie-pro.profile.profile', $extra = [])
    {
        return parent::index($view, $extra);
    }

    /**
     * updateOrganiser
     *
     * @param   Illuminate\Http\Request $request
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function updateSeller(Request $request)
    {
        // demo mode restrictions
        if(config('voyager.demo_mode'))
        {
            return error_redirect('Demo mode');
        }

        
        
        $user = User::find(Auth::id());
        if(Auth::user()->hasRole('organiser')) {

            $this->uploadImage($request, $user);
            $this->sellerInfo($request, $user);

        }

        $user->save();
        // redirect no matter what so that it never turns back
        $msg = __('eventmie-pro::em.saved').' '.__('eventmie-pro::em.successfully');
        return success_redirect($msg, route('eventmie.profile').'#/userSellerInfo');

    }

    /**
     * updateOrganiser
     *
     * @param   Illuminate\Http\Request $request
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function updateMailchimp(Request $request)
    {
        // demo mode restrictions
        if(config('voyager.demo_mode'))
        {
            return error_redirect('Demo mode');
        }
        $user = User::find(Auth::id());

        $this->mailchimp($request, $user);

        $user->save();

        // redirect no matter what so that it never turns back
        $msg = __('eventmie-pro::em.saved').' '.__('eventmie-pro::em.successfully');
        return success_redirect($msg, route('eventmie.profile').'#/userMailchimp');

    }

    /**
     * mailchimp
     *
     * @param   Illuminate\Http\Request $request
     * @param  App\Model\User $user
     * @return void
     */    
    protected function mailchimp(Request $request, $user = null)
    {
        $user->mailchimp_apikey      = $request->mailchimp_apikey;
        $user->mailchimp_list_id     = $request->mailchimp_list_id;
        
    }
    
    /**
     * uploadImage
     *
     * @param   Illuminate\Http\Request $request
     * @param  App\Model\User $user
     * @return void
     */
    protected function uploadImage(Request $request, User $user)
    {
        $path   = 'users/';

        // for image
        if($request->hasfile('avatar')) 
        { 
            $request->validate([
                'avatar' => 'required|image|mimes:jpg,png,jpeg,gif,svg|max:2048',
            
            ]); 
        
            $file = $request->file('avatar');
    
            $extension       = $file->getClientOriginalExtension(); // getting image extension
            $avatar          = time().rand(1,988).'.'.'webp';

            $image_resize    = Image::make($file)->encode('webp', 90)->resize(512, 512, function ($constraint) {
                $constraint->aspectRatio();
            });
            
            // if directory not exist then create directiory
            if (! File::exists(storage_path('/app/public/').$path)) {
                File::makeDirectory(storage_path('/app/public/').$path, 0775, true);
            }
            
            $image_resize->save(storage_path('/app/public/'.$path.$avatar));
            
            $user->avatar    = $path.$avatar;
            
        }
        
        if(empty($user->avatar) || $user->avatar == 'users/default.png')
        {
            $request->validate([
                'avatar' => 'required|image|mimes:jpg,png,jpeg,gif,svg|max:2048',
            
            ]); 
        }

        if(Auth::user()->hasRole('organiser')) 
        {
            if($request->hasfile('seller_signature')) 
            { 
                $request->validate([
                    'seller_signature' => 'required|image|mimes:jpg,png,jpeg,gif,svg|max:2048',
                ]); 
            
                $file = $request->file('seller_signature');
        
                $extension       = $file->getClientOriginalExtension(); // getting image extension
                $avatar          = time().rand(1,988).'.'.$extension;

                // if directory not exist then create directiory
                if (! File::exists(storage_path('/app/public/').$path)) {
                    File::makeDirectory(storage_path('/app/public/').$path, 0775, true);
                }

                $file->storeAs('public/'.$path, $avatar);
                
                $user->seller_signature    = $path.$avatar;
                
            }
            
            if(empty($user->seller_signature) || $user->seller_signature == 'users/default.png')
            {
                $request->validate([
                    'seller_signature' => 'image|mimes:jpg,png,jpeg,gif,svg|max:2048',
                
                ]); 
            }

        }

    }
    
    /**
     * sellerInfo
     *
     * @param   Illuminate\Http\Request $request
     * @param  App\Model\User $user
     * @return void
     */
    protected function sellerInfo(Request $request, User $user)
    {
        $request->validate([
            'seller_name'        => 'max:256',
            'seller_info'        => 'max:256',
            'seller_tax_info'    => 'max:256',
            'seller_note'        => 'max:256',
        ]);

        $user->seller_name       = $request->seller_name;
        $user->seller_info       = $request->seller_info;
        $user->seller_tax_info   = $request->seller_tax_info;
        $user->seller_note       = $request->seller_note;
    }
    
    /**
     * updateAuthUser
     *
     * @param   Illuminate\Http\Request $request
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function updateAuthUser (Request $request)
    {
        // demo mode restrictions
        if(config('voyager.demo_mode'))
        {
            return error_redirect('Demo mode');
        }
        
        $this->validate($request, [
            'name' => 'required|string',
            
            'email' => 'required|email|unique:users,email,'.Auth::id()
        ]);
        
        $user = User::find(Auth::id());

        $user->name                  = $request->name;
        // $user->username              = $request->username;
        $user->email                 = $request->email;
        $user->address               = $request->address;
        $user->phone                 = $request->phone;

        $this->uploadImage($request, $user);

        $user->save();

        // redirect no matter what so that it never turns back
        $msg = __('eventmie-pro::em.saved').' '.__('eventmie-pro::em.successfully');

        if(request()->wantsJson()) {
            return response()->json(['status' => true, 'user' => $user]);
        }
        return success_redirect($msg, route('eventmie.profile'));
        
    }

     /**
     * updateSecurity
     *
     * @param   Illuminate\Http\Request $request
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function updateSecurity (Request $request)
    {   
        // demo mode restrictions
        if(config('voyager.demo_mode'))
        {
            return error_redirect('Demo mode');
        }

        if(!empty($request->current))
        {
            $data = $this->updateAuthUserPassword($request);
            if($data['status'] == false)
            {
                return error_redirect($data['errors']);
            }
        }

         // redirect no matter what so that it never turns back
        $msg = __('eventmie-pro::em.saved').' '.__('eventmie-pro::em.successfully');

         if(request()->wantsJson()) {
            return response()->json(['status' => true, 'user' => Auth::user()]);
        }
        return success_redirect($msg, route('eventmie.profile').'#/userSecurity');
    }

    /**
     * deleteProfile
     *
     * @param  mixed $user
     * @return void
     */
    public function deleteProfile(Request $request)
    {
        Auth::user()->delete();
        return response()->json(['status' => true]);
    }

    public function updateAuthUserRole(Request $request)
    {
        $this->validate($request, [
            'organisation'  => 'required',
        ]);

        $manually_approve_organizer = (int)setting('multi-vendor.manually_approve_organizer');
        
        
        $user = User::find(Auth::id());

        // manually aporove organizer setting on then don't change role
        if (empty($manually_approve_organizer)) {
            //CUSTOM
            $user->roles()->sync([3]);
            //CUSTOM

            $user->role_id      = 3;
        }

        $user->organisation = $request->organisation;

        $user->save();
        
        // ====================== Notification ======================
        // Manual Organizer approval email
        $msg[]                  = __('eventmie-pro::em.name') . ' - ' . $user->name;
        $msg[]                  = __('eventmie-pro::em.email') . ' - ' . $user->email;
        $extra_lines            = $msg;

        $mail['mail_subject']   = __('eventmie-pro::em.requested_to_become_organiser');
        $mail['mail_message']   = __('eventmie-pro::em.become_organiser_notification');
        $mail['action_title']   = __('eventmie-pro::em.view') . ' ' . __('eventmie-pro::em.profile');
        $mail['action_url']     = route('eventmie.profile');
        $mail['n_type']         = "Approve-Organizer";
        if (empty($manually_approve_organizer)) {
            // Became Organizer successfully email
            $msg[]                  = __('eventmie-pro::em.name') . ' - ' . $user->name;
            $msg[]                  = __('eventmie-pro::em.email') . ' - ' . $user->email;
            $extra_lines            = $msg;

            $mail['mail_subject']   = __('eventmie-pro::em.became_organiser_successful');
            $mail['mail_message']   = __('eventmie-pro::em.became_organiser_successful_msg');
            $mail['action_title']   = __('eventmie-pro::em.view') . ' ' . __('eventmie-pro::em.profile');
            $mail['action_url']     = route('eventmie.profile');
            $mail['n_type']         = "Approved-Organizer";
        }
        
        /* CUSTOM */
        $mail['user'] = $user;
        /* CUSTOM */

        // notification for
        $notification_ids       = [
            // 1, // admin
            $user->id, // logged in user by
        ];

        $users = User::whereIn('id', $notification_ids)->get();

        $user = $user;

        try {
            // \Notification::locale(\App::getLocale())->send($users, new MailNotification($mail, $extra_lines));
            //CUSTOM
            
            \App\Jobs\RegistrationEmailJob::dispatch($mail, $users, 'become_organizer')->delay(now()->addSeconds(10));
            // test
            // return view('email_templates.becomeOrganizer', compact('mail'));
            //CUSTOM
        } catch (\Throwable $th) {
            
        }
        
        // ====================== Notification ======================

         if(checkPrefix())
            return response()->json(['status' => true, 'user' => Auth::user(), 'msg' => $mail['mail_message'],  'approval_by_admin' => $manually_approve_organizer]);

        return redirect()->route('eventmie.profile');
    }

    /**
     * updateOrganiser
     *
     * @param   Illuminate\Http\Request $request
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function updateOrganiser(Request $request)
    {
        // demo mode restrictions
        if(config('voyager.demo_mode'))
        {
            return error_redirect('Demo mode');
        }

        $request->validate([
            'organisation' => 'required|unique:users,organisation,'.Auth::id(),

        ]);

        $user = User::find(Auth::id());
        if(Auth::user()->hasRole('organiser')) {
            $user->organisation          = $request->organisation;
            $user->org_description       = $request->org_description;
            $user->org_facebook          = $request->org_facebook;
            $user->org_instagram         = $request->org_instagram;
            $user->org_youtube           = $request->org_youtube;
            $user->org_twitter           = $request->org_twitter;
            $user->org_website           = $request->org_website;
        }


        $user->save();

        $this->updateAuthUserRole($request);

        // redirect no matter what so that it never turns back
        $msg = __('eventmie-pro::em.saved').' '.__('eventmie-pro::em.successfully');
        return success_redirect($msg, route('eventmie.profile').'#/userOrganiserInfo');

    }
}
