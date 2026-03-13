<?php

use App\Http\Controllers\AobgAdvaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ProfessionController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\EntryProcessController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\generateCustomId;
use App\Http\Controllers\PaymentStatusController;
use App\Http\Controllers\PendingStatusController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;
use App\Http\Controllers\PeopleController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\SmeController;
use App\Http\Controllers\AccountantController;
use App\Http\Controllers\PublicationManagerController;
use App\Http\Controllers\OperationalReportsController;

/*************
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
 */

//csrf token
// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', [RegisterController::class, 'login'])->name('login');
Route::post('/auto-login', [RegisterController::class, 'autoLogin']);

Route::post('/check-login', [RegisterController::class, 'checklogin']);
Route::post('/logoutcheck', [RegisterController::class, 'logoutlogin']);

Route::middleware('auth:sanctum')->group(function () {});

Route::post('/forget-password', [ForgotPasswordController::class, 'submitForgetPasswordForm'])->name('forget.password.post');
Route::post('/reset-password', [ForgotPasswordController::class, 'submitResetPasswordForm'])->name('reset.password.post');

//generateCustomId
Route::post('/customId', [generateCustomId::class, 'generateCustomId']);

//InstitutionController
Route::prefix('institutions')->controller(InstitutionController::class)->group(function () {
    Route::get('/list', 'index')->name('institution.index');
    Route::post('/store', 'store')->name('institution.store');
    Route::get('/view/{id}', 'show')->name('institution.show');
    Route::put('/update/{id}', 'update')->name('institution.update');
    Route::delete('/delete/{id}', 'destroy')->name('institution.destroy');
});

Route::prefix('peoples')->controller(PeopleController::class)->group(function () {
    Route::get('/list', 'index');
});

//DepartmentController
Route::prefix('department-types')->controller(DepartmentController::class)->group(function () {
    Route::get('/list', 'index')->name('department.index');
    Route::post('/store', 'store')->name('department.store');
    Route::get('/view/{id}', 'show')->name('department.show');
    Route::put('/update/{id}', 'update')->name('department.update');
    Route::delete('/delete/{id}', 'destroy')->name('department.destroy');
});
//ProfessionController
Route::prefix('profession-types')->controller(ProfessionController::class)->group(function () {
    Route::get('/list', 'index')->name('profession.index');
    Route::post('/store', 'store')->name('profession.store');
    Route::get('/view/{id}', 'show')->name('profession.show');
    Route::put('/update/{id}', 'update')->name('profession.update');
    Route::delete('/delete/{id}', 'destroy')->name('profession.destroy');
});
//Entry Process
Route::prefix('entry_process')->controller(EntryProcessController::class)->group(function () {
    Route::get('/process-report', 'getProcessReport');
    Route::get('/list', 'index')->name('entry.index');
    Route::get('/publication-list', 'indexPub')->name('entry.pub');
    Route::post('/store', 'store')->name('entry.store');
    Route::get('/view/{id}', 'show')->name('entry.show');
    Route::get('/project-view-data/{id}', 'showProjectView');
    Route::post('/update/{id}', 'update')->name('entry.update');
    Route::delete('/delete/{id}', 'destroy')->name('entry.destroy');
    Route::delete('/delete-project', 'deleteProjectById');
    Route::get('/filter/{id}', 'filterID')->name('entry.filterID');
    Route::get('/institutions', 'fetchInstitutions');
    Route::get('/departments', 'fetchDepartments');
    Route::get('/professions', 'fetchProfessions');
    Route::get('/projecttitle', 'fetchProjectTitle');
    Route::get('/projecttitlelist', 'fetchProjectTitleE');
    Route::get('/project-view/{id}', 'projectViewEntry');
    Route::get('/get-email', 'getEmails');
    Route::post('/project-status/{id}', 'projectStatusView');
    Route::delete('/document-delete/{id}', 'documentDelete');
    Route::post('/document-rename/{id}', 'documentRenameDoc');
    Route::get('/getPendingList/{id}', 'getPendingList');
    Route::get('/client_notification/{id}', 'getClientNotification');
    Route::get('/project_client_notification/{id}', 'getClientProjectNotification');
    Route::get('/show_Client_Project_Notification/{id}', 'showClientProjectNotification');
    Route::get('/project-list', 'getProjectList');
    Route::get('/project-emp-list', 'getProjectEmpList');
    Route::get('/emp-project-list', 'getEmpProjectList');
    Route::get('/employee-project-list', 'getEmployeeProjectList');
    Route::get('/project-payment-list', 'getEmpPayment');
    Route::get('/project-payment-lists', 'getEmpPayments');
    Route::get('/project-list-view', 'getProjectView');
    Route::get('/deleted-list', 'getProjectDeleteList');
    Route::get('/project_status_change/{id}', 'getProjectStatusChange');
    Route::post('/project_sme_status/{id}', 'getSupporStatusChange');
    Route::post('/update_status', 'updateProjectStatus');

    Route::get('/activity-list', 'getProjectActivity');
    Route::put('/updateProjectId/{project_id}', [EntryProcessController::class, 'updateProjectId']);
    Route::get('/getEmployeeName', [EntryProcessController::class, 'getEmployeeName']);
    Route::get('/getEmployeeNames', [EntryProcessController::class, 'getEmployeeNames']);
    Route::get('/getEmployeeNameReport', [EntryProcessController::class, 'getEmployeeNameReport']);
    Route::get('/projectLog', [EntryProcessController::class, 'projectLog']);

    //projectID get
    Route::get('/projectId/{project_id}', [EntryProcessController::class, 'showProjectId']);
    Route::get('/indexProjectId', [EntryProcessController::class, 'indexProjectId']);
    //fetching the count and params
    Route::get('/processList', [EntryProcessController::class, 'fetchProjectId']);
    Route::get('/dashboarprocessList', [EntryProcessController::class, 'dashboardProjectList']);
    Route::get('/dashboarwrprocessList', [EntryProcessController::class, 'dashboardWRList']);
    Route::get('/project-income', [EntryProcessController::class, 'projectIncome']);
    Route::get('/document-download', 'getProjectDocumentDownload');
    Route::delete('/project_assign_delete/{id}', 'getProjectAssignDelete');
    Route::post('/assign-user-tc', 'assignUserByTc');
    Route::get('/freelancer-project-list', 'getFreelancerDetails');
    Route::get('/trackingStatus', 'trackingStatus');

    Route::get('/inhouse', [EntryProcessController::class, 'inhouseExternal']);
    Route::get('/tcdashboard', [EntryProcessController::class, 'tcDashboard']);
    Route::get('/pmDashboard', [EntryProcessController::class, 'pmDashboard']);
    Route::get('/adminDashboard', [EntryProcessController::class, 'adminDashboard']);
    Route::get('/client-details-by-number', [EntryProcessController::class, 'findPhoneNumber']);
});

//sme dashboard

Route::prefix('sme')->controller(SmeController::class)->group(function () {
    Route::get('/list', 'index');
});

Route::get('/setting-list', [ProfileController::class, 'settinglist']);
Route::post('/setting', [ProfileController::class, 'settingUpdate'])->name('profile.setting');
//profileController
Route::post('/profile', [ProfileController::class, 'store'])->name('profile.store');
Route::put('/profile/{id}', [ProfileController::class, 'update'])->name('profile.update');

// File download route
Route::get('/download/{file}', [PaymentStatusController::class, 'download']);

//PaymentStatus

Route::prefix('payment_processes')->controller(PaymentStatusController::class)->group(function () {
    Route::get('/list', 'index')->name('payment.index');
    Route::post('/store', 'store')->name('payment.store');
    Route::get('/view/{id}', 'show')->name('payment.show');
    Route::post('/update/{id}', 'update')->name('payment.update');
    Route::delete('/delete/{id}', 'destroy')->name('payment.destroy');
    Route::delete('/payment_delete/{id}', 'paymentDelete');
    Route::post('/payments', 'storePaymentData');
    Route::get('/payment-view/{id}', 'showPayment');
    Route::get('/payment-list', 'getPaymentList');
    Route::post('/payment-status', 'statusChange');
    Route::get('/get-client-details/{id}', 'getPaymentDetails');

    Route::get('/getfreelancerdetails', 'getfreelancerdetails');
    Route::delete('/payment-delete','deletePaymentById');
});

Route::prefix('projects')->controller(ProjectController::class)->group(function () {
    Route::get('/list', 'index');
    Route::post('/store', 'store');
    Route::post('/rejectreason', 'rejectReason');
    Route::post('/rejectreason-view', 'rejectReasonView');
    Route::post('/document_uploads', 'documentUpload');
    Route::get('/project-activity', 'getProjectActivity');
    Route::post('/project-reply-activity', 'storeReplyActivity');
    Route::post('/project-activity-update', 'updateActivity');
    Route::post('/project-reply-update', 'updateActivityReply');
    Route::get('/project-activity-reply', 'getProjectReply');
    Route::post('/mark-reply-as-read', 'markReplyAsRead');
    Route::get('/getNotification-task', 'getNotification_task');
    Route::get('/notification-view', 'getNotification');
    Route::post('/mark-notifications-read/{id}', 'unreadNotification');
    Route::get('/budget-chat', 'getTypeOfWorkByBudget');
    Route::get('/income-expense', 'getIncome_Expense');
});
//report Controller
Route::prefix('reports')->controller(ReportsController::class)->group(function () {
    //process report
    Route::get('/get-report', 'getProcessReport');
    Route::get('/get-project-payment', 'getProjectPayment');


    //Client analysis report
    Route::get('/get-department-report', 'getDepartmentReport');
    Route::get('/get-profession-report', 'getProfessionReport');
    Route::get('/get-institute-report', 'getInstituteReport');

    //client details report
    Route::get('/get-client-report', 'getClientDetails');

    //project List
    Route::get('/get-projectList-report', 'projectList');
    Route::get('/journal-status', 'journalStatus');

    Route::get('/get-yearly-report', 'yearly_process_report');
    Route::get('/get-yearly-income', 'getTypeOfWorkReport');
    Route::get('/get-total-payment', 'getTotalPayment');
    Route::get('/project-pending', 'projectPending');
    Route::get('/employee-type-report', 'employeeTypeReports');
    Route::get('/employee-performance-report', 'getEmployeePerformanceReport');
});



Route::prefix('author')->controller(AuthorController::class)->group(function () {
    Route::get('/test', 'getTemplate');
    Route::get('/author-list', 'author_list');
    Route::post('/add-author', 'store');
    Route::post('/edit-author', 'edit');
    Route::post('/delete-author', 'delete');

    Route::post('/add-submission', 'submission_store');
    Route::get('/submission-list', 'submission_list');

    Route::post('/reviewer-comment', 'reviewerComments_store');
    Route::get('/reviewer-list', 'reviewer_list');


    Route::post('/rejected-form', 'rejectedForm_store');
    Route::get('/rejected-list', 'rejected_list');


    Route::post('/resubmission-form', 'resubmitted_store');
    Route::get('/resubmission-list', 'resubmitted_list');


    //documents Forms
    Route::post('/document-store', 'documentForm');
    Route::get('/document-list', 'document_list');
    Route::post('/document-delete/{id}', 'document_delete');


    //author-view
    Route::get('/author-view/{id}', 'author_view');
    Route::get('/publication-status', 'get_publication_list');
    Route::post('/publication-support', 'get_support_publication');
});

Route::prefix('publication_manager')->controller(PublicationManagerController::class)->group(function () {
    Route::get('/publication-list', 'publication_dashboard');
});

Route::prefix('accountant')->controller(AccountantController::class)->group(function () {
    Route::get('/payment-task', 'payment_task');
    Route::get('/accountant_list', 'accountant_count_list');
    Route::get('/payment-invoice', 'payment_invoice');


    Route::post('/store-invoice-details', 'storeInvoiceDetails');
    Route::get('/get-invoice-details', 'getInvoiceDetails');
    Route::get('/invoice-mail', 'getInvoiceDetailsAndSendMail');
});

Route::prefix('operational-report')->controller(OperationalReportsController::class)->group(function () {

    Route::get('/project-durations', 'getProjectDuration');
    Route::get('/operational-durations', 'getOperationalDuration');
});
