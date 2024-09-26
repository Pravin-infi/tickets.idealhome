<?php

namespace App\Http\Controllers\Eventmie\Voyager;

use App\Models\User;
use Illuminate\Http\Request;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Events\BreadDataDeleted;
use Classiebit\Eventmie\Http\Controllers\Voyager\VoyagerUserController as BaseVoyagerUserController;


class VoyagerUserController extends BaseVoyagerUserController
{
    
    protected function registrationNotification($user)
    {
        // send signup notification
        // ====================== Notification ====================== 
        $mail['mail_subject']   = __('eventmie-pro::em.register_success');
        $mail['mail_message']   = __('eventmie-pro::em.get_tickets');
        $mail['action_title']   = __('eventmie-pro::em.login');
        $mail['action_url']     = eventmie_url();
        $mail['n_type']         = "user";

        // notification for
        $notification_ids       = [
            1, // admin
            $user->id, // new registered user
        ];
        
        $users = User::whereIn('id', $notification_ids)->get();
     
        if(checkMailCreds()) 
            {
                try {
                    // \Notification::locale(\App::getLocale())->send($users, new MailNotification($mail));
                    //CUSTOM
                    \App\Jobs\RegistrationEmailJob::dispatch($mail, $users, 'register')->delay(now()->addSeconds(10));
                    //CUSTOM
                } catch (\Throwable $th) {}
            }
        // ====================== Notification ======================     
    }
    
    protected function approvedOrganiserNotification($user)
    {
        // ====================== Notification ====================== 
        
        // Became Organizer successfully email
        $msg[]                  = __('eventmie-pro::em.name').' - '.$user->name;
        $msg[]                  = __('eventmie-pro::em.email').' - '.$user->email;
        $extra_lines            = $msg;

        $mail['mail_subject']   = __('eventmie-pro::em.became_organiser_successful');
        $mail['mail_message']   = __('eventmie-pro::em.became_organiser_successful_msg');
        $mail['action_title']   = __('eventmie-pro::em.view').' '.__('eventmie-pro::em.profile');
        $mail['action_url']     = route('eventmie.profile');
        $mail['n_type']         = "Approved-Organizer";
        
        
        /* CUSTOM */
        $mail['user'] = $user;
        /* CUSTOM */

        // notification for
        $notification_ids       = [
            1, // admin
            $user->id, // the organizer
        ];
        
        $users = User::whereIn('id', $notification_ids)->get();
        
        if(checkMailCreds()) 
            {
                try {
                    // \Notification::locale(\App::getLocale())->send($users, new MailNotification($mail));
                    //CUSTOM
                    \App\Jobs\RegistrationEmailJob::dispatch($mail, $users, 'become_organizer')->delay(now()->addSeconds(10));
                    //CUSTOM
                } catch (\Throwable $th) {
                    
                }
            }
        // ====================== Notification ====================== 

    }

    /**
     * Delete BREAD.
     *
     * @param Number $id BREAD data_type id.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, $id)
    {
        
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Init array of IDs
        $ids = [];
        if (empty($id)) {
            // Bulk delete, get IDs from POST
            $ids = explode(',', $request->ids);
        } else {
            // Single item delete, get ID from URL
            $ids[] = $id;
        }

        $affected = 0;
        
        foreach ($ids as $id) {
            $data = call_user_func([$dataType->model_name, 'findOrFail'], $id);

            // Check permission
            $this->authorize('delete', $data);

            $model = app($dataType->model_name);
            if (!($model && in_array(SoftDeletes::class, class_uses_recursive($model)))) {
                $this->cleanup($dataType, $data);
            }

            $res = $data->forceDelete();

            if ($res) {
                $affected++;

                event(new BreadDataDeleted($dataType, $data));
            }
        }

        $displayName = $affected > 1 ? $dataType->getTranslatedAttribute('display_name_plural') : $dataType->getTranslatedAttribute('display_name_singular');

        $data = $affected
            ? [
                'message'    => __('voyager::generic.successfully_deleted')." {$displayName}",
                'alert-type' => 'success',
            ]
            : [
                'message'    => __('voyager::generic.error_deleting')." {$displayName}",
                'alert-type' => 'error',
            ];

        return redirect()->route("voyager.{$dataType->slug}.index")->with($data);
    }
}
