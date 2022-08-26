<?php

use App\Http\Controllers\Admin\CheckInSurveyController;
use App\Http\Controllers\Admin\EmployeeDashboardCmsController;
use App\Http\Controllers\Admin\LearningPlanController;
use App\Http\Controllers\Admin\LearningPlanFileController;
use App\Http\Controllers\Admin\OpportunityController;
use App\Http\Controllers\Admin\PopupSurveyController;
use App\Http\Controllers\Admin\PostWorkshopSurveyController;
use App\Http\Controllers\Admin\ProfileTypeController;
use App\Http\Controllers\Admin\StepController;
use App\Http\Controllers\Admin\TodoController;
use App\Http\Controllers\Admin\ZoomMeetingController;
use App\Http\Controllers\Common\ChargebeeController;
use App\Http\Controllers\Common\HomeController;
use App\Http\Controllers\Common\ProfileController;
use App\Http\Controllers\Common\RequestWorkshopController;
use App\Http\Controllers\Employer\AnnouncementController;
use App\Http\Controllers\Employer\EmployerController;
use App\Http\Controllers\Common\MessageController;
use App\Http\Controllers\Employer\ResourceController;
use App\Http\Controllers\Employer\TeamController;
use App\Http\Controllers\Employer\WelcomeNoteController;
use App\Http\Controllers\WorkshopController;
use App\Models\PopupSurveyQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([
    'middleware' => ['cors']],function () {

//    Route::post('/create-customer', [ChargebeeController::class, 'create_customer']);
//    Route::get('/webhook-listen',[ChargebeeController::class,'webhook_listen']);

    ###################################################################
    /**************************Registration Routes********************/
    ###################################################################

    Route::post('/create-company', [HomeController::class, 'create_company']);
    Route::post('/create-company-employee', [HomeController::class, 'create_company_employee']);
    Route::get('/get-plans',[ChargebeeController::class,'get_plans']);
    Route::get('/get-plans2',[ChargebeeController::class,'get_plans2']);
    Route::get('/get-addons',[ChargebeeController::class,'get_addons']);
    Route::post('/select-addon/{id}',[ChargebeeController::class,'select_addon']);
    Route::post('/create-estimate',[ChargebeeController::class,'create_estimate']);
    Route::post('/create-subscription', [ChargebeeController::class, 'create_subscription']);
    Route::get('/update-payment-status/{link}', [ChargebeeController::class, 'update_payment_status']);
    Route::get('/get-countries', [HomeController::class, 'get_countries']);

    ###################################################################
    /***************************Before Login Routes*******************/
    ###################################################################

    Route::post('/login', [HomeController::class, 'login']);
    Route::post('/forgot-password-send-email', [HomeController::class, 'send_email']);
    Route::post('/reset-password', [HomeController::class, 'reset_password']);
    Route::post('/create-password', [HomeController::class, 'create_password']);


    Route::get('/get-company-details/{link}', [EmployerController::class, 'get_company_details']);
//    Route::get('/get-plan-details-by-subscription-id/{id}',[ChargebeeController::class,'get_plan_details_by_subscription_id']);
    Route::get('/get-profile-type-list', [ProfileTypeController::class, 'get_profile_type_list']);

    Route::get('/get-check-in-survey-questions/{id}', [CheckInSurveyController::class, 'get_check_in_survey_questions']);
    Route::post('/submit-check-in-survey/{id}', [CheckInSurveyController::class, 'submit_check_in_survey']);

    Route::get('/get-post-workshop-survey-questions/{id}', [PostWorkshopSurveyController::class, 'get_post_workshop_survey_questions']);
    Route::post('/submit-post-workshop-survey/{id}/{w_id}', [PostWorkshopSurveyController::class, 'submit_post_workshop_survey']);

});
Route::group(['middleware' => ['auth:api', 'cors']], function () {

    Route::post('/upload-logo', [EmployerController::class, 'upload_logo']);
    Route::post('/ask-question', [EmployerController::class, 'ask_question']);
    Route::post('/get-question-list', [EmployerController::class, 'get_question_list']);
    Route::post('/get-companies-list', [EmployerController::class, 'get_company_list']);

    ###################################################################
    /****************************Common Routes************************/
    ###################################################################


    Route::get('/logout', [HomeController::class, 'logout']);
    Route::get('/get-company-list', [HomeController::class, 'get_company_list']);
    Route::get('/get-auth-user', [ProfileController::class, 'get_auth_user']);
    Route::post('/update-profile', [ProfileController::class, 'update_profile']);
    Route::post('/update-profile-company', [ProfileController::class, 'update_profile_company']);
    Route::get('/active-inactive-company/{id}/{status}',[ProfileController::class, 'active_inactive_company']);
    Route::post('/upload-profile-image', [ProfileController::class, 'upload_profile_image']);
    Route::post('/change-password', [ProfileController::class, 'change_password']);

    ###################################################################
    /**************************Employer Routes************************/
    ###################################################################

    //company feedback routes

    Route::post('/submit-company-feedback', [EmployerController::class, 'submit_company_feedback']);
    Route::get('/get-company-feedback-list', [EmployerController::class, 'get_company_feedback_list']);


    //Announcement routes

    Route::post('/add-announcement', [AnnouncementController::class, 'add_announcement']);
    Route::get('/delete-announcement/{id}', [AnnouncementController::class, 'delete_announcement']);
    Route::get('/get-announcement/{id}', [AnnouncementController::class, 'get_announcement']);
    Route::post('/update-announcement', [AnnouncementController::class, 'update_announcement']);
    Route::get('/get-announcements-list/{id}', [AnnouncementController::class, 'get_announcements_list']);
    Route::post('/get-all-announcements-list/{id}', [AnnouncementController::class, 'get_all_announcements_list']);

    //Resources routes

    Route::post('/add-resource', [ResourceController::class, 'add_resource']);
    Route::post('/get-resources-list', [ResourceController::class, 'get_resources_list']);
    Route::get('/get-resource/{id}', [ResourceController::class, 'get_resource']);
    Route::post('/update-resource', [ResourceController::class, 'update_resource']);
    Route::get('/delete-resource/{id}', [ResourceController::class, 'delete_resource']);
    Route::get('/download-file/{id}', [ResourceController::class, 'download_file']);
    Route::get('/get-resources-list-dashboard', [ResourceController::class, 'get_resources_list_dashboard']);

    //Team and Organization Management Routes

    Route::post('/get-employees-list/{id}', [TeamController::class, 'get_employees_list']);
    Route::get('/delete-employee/{id}', [TeamController::class, 'delete_employee']);
    Route::get('/get-employee/{id}', [TeamController::class, 'get_employee']);
    Route::post('/update-employee', [TeamController::class, 'update_employee']);
    Route::post('/export-employees/{id}', [TeamController::class, 'export_employees']);

    Route::get('/get-employee-registration-link', [TeamController::class, 'get_employee_registration_link']);
    Route::post('/send-link-to-email', [TeamController::class, 'send_link_to_email']);
    Route::post('/get-invitations-list/{id}', [TeamController::class, 'get_invitations_list']);

    //Welcome Note Routes

    Route::post('/add-welcome-note', [WelcomeNoteController::class, 'add_welcome_note']);
    Route::post('/add-welcome-note-company', [WelcomeNoteController::class, 'add_welcome_note_company']);
    Route::post('/update-welcome-note-company', [WelcomeNoteController::class, 'update_welcome_note_company']);
    Route::get('/delete-welcome-note/{id}', [WelcomeNoteController::class, 'delete_welcome_note']);
    Route::get('/get-welcome-note', [WelcomeNoteController::class, 'get_welcome_note']);
    Route::get('/get-single-welcome-note/{id}', [WelcomeNoteController::class, 'get_single_welcome_note']);
    Route::get('/get-single-welcome-note-company', [WelcomeNoteController::class, 'get_single_welcome_note_company']);
    Route::get('/get-welcome-note-list', [WelcomeNoteController::class, 'get_welcome_note_list']);
    Route::get('/get-welcome-note-company-list', [WelcomeNoteController::class, 'get_welcome_note_company_list']);

    //Membership routes

    Route::get('/get-plan-details-by-subscription-id/{id}',[ChargebeeController::class,'get_plan_details_by_subscription_id']);

    ###################################################################
    /******************************Common Routes**********************/
    ###################################################################

    //Chat routes

    Route::post('/send-group-message', [MessageController::class, 'send_group_message']);
    Route::post('/get-group-message', [MessageController::class, 'get_group_message']);
    Route::post('/get-one-to-one-message/{rId}', [MessageController::class, 'get_one_to_one_message']);
    Route::post('/send-one-to-one-message', [MessageController::class, 'send_one_to_one_message']);
    Route::post('/send-attachments', [MessageController::class, 'send_attachments']);
    Route::post('/download-attachments', [MessageController::class, 'download_attachment']);

    ###################################################################
    /****************************Admin Routes*************************/
    ###################################################################

    //Steps Routes

    Route::post('/add-step', [StepController::class, 'add_step']);
    Route::post('/get-steps-list', [StepController::class, 'get_steps_list']);
    Route::get('/get-step/{id}', [StepController::class, 'get_step']);
    Route::post('/update-step', [StepController::class, 'update_step']);
    Route::get('/delete-step/{id}', [StepController::class, 'delete_step']);
    Route::post('/upload-toolkit', [StepController::class, 'upload_toolkit']);
    Route::post('/delete-toolkit/{id}', [StepController::class, 'delete_toolkit']);
    Route::get('/download-toolkit/{id}', [StepController::class, 'download_toolkit']);
    Route::post('/upload-guide-book', [StepController::class, 'upload_guide_book']);

    //opportunity routes

    Route::post('/add-opportunity', [OpportunityController::class, 'add_opportunity']);
    Route::post('/update-opportunity', [OpportunityController::class, 'update_opportunity']);
    Route::get('/get-opportunity/{id}', [OpportunityController::class, 'get_opportunity']);
    Route::get('/get-opportunity-list', [OpportunityController::class, 'get_opportunity_list']);
    Route::get('/delete-opportunity/{id}', [OpportunityController::class, 'delete_opportunity']);
    Route::get('/get-opportunity-list-dashboard', [OpportunityController::class, 'get_opportunity_list_dashboard']);

    //todo routes

    Route::post('/add-todo', [TodoController::class, 'add_todo']);
    Route::post('/update-todo', [TodoController::class, 'update_todo']);
    Route::get('/get-todo/{id}', [TodoController::class, 'get_todo']);
    Route::post('/get-todo-list', [TodoController::class, 'get_todo_list']);
    Route::get('/delete-todo/{id}', [TodoController::class, 'delete_todo']);
    Route::get('/complete-todo/{id}', [TodoController::class, 'complete_todo']);
    Route::get('/get-todo-list-dashboard', [TodoController::class, 'get_todo_list_dashboard']);

    //request workshop routes

    Route::post('/request-workshop', [RequestWorkshopController::class, 'request_workshop']);
    Route::post('/get-workshop-list', [RequestWorkshopController::class, 'get_workshop_list']);
    Route::get('/get-request-workshop-list-dashboard', [RequestWorkshopController::class, 'get_workshop_list_dashboard']);
    Route::get('/delete-request-workshop/{id}', [RequestWorkshopController::class, 'delete_request_workshop']);
    Route::get('/accept-request-workshop/{id}', [RequestWorkshopController::class, 'accept_request_workshop']);
    Route::get('/reject-request-workshop/{id}', [RequestWorkshopController::class, 'reject_request_workshop']);

    //Profile type routes

    Route::post('/add-profile-type', [ProfileTypeController::class, 'add_profile_type']);
    Route::post('/update-profile-type', [ProfileTypeController::class, 'update_profile_type']);
    Route::get('/delete-profile-type/{id}', [ProfileTypeController::class, 'delete_profile_type']);
    Route::get('/download-profile-type-file/{id}', [ProfileTypeController::class, 'download_profile_type_file']);
    Route::get('/get-profile-type/{id}', [ProfileTypeController::class, 'get_profile_type']);

    //Popup surveys routes

    Route::post('/add-popup-survey', [PopupSurveyController::class, 'add_popup_survey_question']);
    Route::post('/update-popup-survey', [PopupSurveyController::class, 'update_popup_survey_question']);
    Route::get('/delete-popup-survey/{id}', [PopupSurveyController::class, 'delete_popup_survey_question']);
    Route::get('/get-popup-survey/{id}', [PopupSurveyController::class, 'get_popup_survey_question']);
    Route::get('/get-popup-survey-answer-list/{id}', [PopupSurveyController::class, 'get_popup_survey_answer_list']);
    Route::post('/get-popup-survey-list', [PopupSurveyController::class, 'get_popup_survey_question_list']);
    Route::get('/get-survey-questions-dashboard', [PopupSurveyController::class, 'get_survey_questions_dashboard']);
    Route::post('/submit-popup-survey', [PopupSurveyController::class, 'submit_popup_survey']);
    Route::get('/get-chart-data',[PopupSurveyController::class,'get_chart_data']);


    //CheckIn surveys routes

    Route::post('/add-check-in-survey', [CheckInSurveyController::class, 'add_check_in_survey_question']);
    Route::post('/update-check-in-survey', [CheckInSurveyController::class, 'update_check_in_survey_question']);
    Route::get('/delete-check-in-survey/{id}', [CheckInSurveyController::class, 'delete_check_in_survey_question']);
    Route::get('/get-check-in-survey/{id}', [CheckInSurveyController::class, 'get_check_in_survey_question']);
    Route::get('/get-check-in-survey-answer-list', [CheckInSurveyController::class, 'get_check_in_survey_answer_list']);
    Route::post('/get-check-in-survey-list', [CheckInSurveyController::class, 'get_check_in_survey_question_list']);
    Route::get('/send-email', [CheckInSurveyController::class, 'send_email']);

    //Post Workshop surveys routes

    Route::post('/add-post-workshop-survey', [PostWorkshopSurveyController::class, 'add_post_workshop_survey_question']);
    Route::post('/update-post-workshop-survey', [PostWorkshopSurveyController::class, 'update_post_workshop_survey_question']);
    Route::get('/delete-post-workshop-survey/{id}', [PostWorkshopSurveyController::class, 'delete_post_workshop_survey_question']);
    Route::get('/get-post-workshop-survey/{id}', [PostWorkshopSurveyController::class, 'get_post_workshop_survey_question']);
    Route::get('/get-post-workshop-survey-answer-list', [PostWorkshopSurveyController::class, 'get_post_workshop_survey_answer_list']);
    Route::post('/get-post-workshop-survey-list', [PostWorkshopSurveyController::class, 'get_post_workshop_survey_question_list']);
    Route::get('/send-post-workshop-survey-email/{id}', [PostWorkshopSurveyController::class, 'send_email']);


    //Employee Dashboard CMS routes

    Route::post('/add-update-section1', [EmployeeDashboardCmsController::class, 'add_update_section1']);
    Route::post('/add-update-section2', [EmployeeDashboardCmsController::class, 'add_update_section2']);
    Route::post('/add-update-section3', [EmployeeDashboardCmsController::class, 'add_update_section3']);

    Route::get('/get-section1/{id}', [EmployeeDashboardCmsController::class, 'get_section1']);
    Route::get('/get-section2/{id}', [EmployeeDashboardCmsController::class, 'get_section2']);
    Route::get('/get-section3/{id}', [EmployeeDashboardCmsController::class, 'get_section3']);

    Route::get('/delete-section3-image/{id}', [EmployeeDashboardCmsController::class, 'delete_section3_image']);
    Route::get('/download-learning-tools/{id}', [EmployeeDashboardCmsController::class, 'download_learning_tools']);

    //Workshops Routes

    Route::post('/add-workshop', [WorkshopController::class, 'add_workshop']);
    Route::post('/get-workshops-list', [WorkshopController::class, 'get_workshops_list']);
    Route::get('/get-workshops-list-for-select', [WorkshopController::class, 'get_workshops_list_for_select']);
    Route::get('/get-workshop/{id}', [WorkshopController::class, 'get_workshop']);
    Route::post('/update-workshop', [WorkshopController::class, 'update_workshop']);
    Route::get('/delete-workshop/{id}', [WorkshopController::class, 'delete_workshop']);
    Route::get('/register-for-workshop/{id}', [WorkshopController::class, 'register_for_workshop']);
    Route::get('/get-workshop-list-dashboard', [WorkshopController::class, 'get_workshop_list_dashboard']);

    //Zoom Meeting routes

    Route::post('/add-meeting', [ZoomMeetingController::class, 'store']);
    Route::get('/get-meeting-recording-list/{id}', [ZoomMeetingController::class, 'get_recordings']);
    Route::get('/get-meeting/{id}', [ZoomMeetingController::class, 'show']);
    Route::post('/get-meetings-list', [ZoomMeetingController::class, 'get_meetings_list']);
    Route::get('/delete-meeting/{id}', [ZoomMeetingController::class, 'destroy']);


    //learning plan routes

    Route::post('/add-learning-plan', [LearningPlanController::class, 'add_learning_plan']);
    Route::post('/update-learning-plan', [LearningPlanController::class, 'update_learning_plan']);
    Route::get('/get-learning-plan/{id}', [LearningPlanController::class, 'get_learning_plan']);
    Route::post('/get-learning-plan-list', [LearningPlanController::class, 'get_learning_plan_list']);
    Route::get('/delete-learning-plan/{id}', [LearningPlanController::class, 'delete_learning_plan']);
    Route::post('/get-learning-plan-list-dashboard', [LearningPlanController::class, 'get_learning_plan_list_dashboard']);


    //learning plan files routes

    Route::post('/add-learning-plan-file', [LearningPlanFileController::class, 'add_learning_plan_file']);
    Route::post('/update-learning-plan-file', [LearningPlanFileController::class, 'update_learning_plan_file']);
    Route::get('/delete-learning-plan-file/{id}', [LearningPlanFileController::class, 'delete_learning_plan_file']);
    Route::get('/download-learning-plan-file/{id}', [LearningPlanFileController::class, 'download_learning_plan_file']);
    Route::get('/get-learning-plan-files/{id}', [LearningPlanFileController::class, 'get_learning_plan_files']);


});

Route::post('/update-meeting/{id}', [ZoomMeetingController::class, 'update']);
