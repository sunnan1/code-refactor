<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(BookingRequest $request) // better to use FormRequest to validate your input
    {
        $response = [];
        $userId = $request->input('user_id');
        $authenticatedUser = $request->__authenticatedUser ?? null;
        if ($userId) {
            $response = $this->repository->getUsersJobs($userId) ?? [];
        } elseif ($authenticatedUser) {
            $userType = $authenticatedUser->user_type;
            $adminRoleId = config('app.ADMIN_ROLE_ID');
            $superAdminRoleId = config('app.SUPERADMIN_ROLE_ID');
            if ($userType === $adminRoleId || $userType === $superAdminRoleId) {
                $response = $this->repository->getAll($request) ?? [];
            } else {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        } else {
            return response(['error' => 'Invalid request'];
        }
        return response($response);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id) // Best is to Implement Route model binding but as not aware of Implementation of Repository so its seems fine for now
    {
        $job = $this->repository->with('translatorJobRel.user')->find($id);
        if (!$job) {
            return response(['error' => 'Job not found']);
        }
        return response($job);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(BookingRequest $request) // Booking Request has to be used
    {
        try {
            $data = $request->validated();
            $response = $this->repository->store($request->__authenticatedUser, $data);
            return response($response);
        }catch (\Exception $exception) {
            return response($exception->getMessage());
        }
    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, BookingRequest $request)
    {
        try {
            $data = $request->validated();
            $cuser = $request->__authenticatedUser;
            $response = $this->repository->updateJob($id, array_except($data, ['_token', 'submit']), $cuser);

            return response($response);
        }catch (\Exception $exception) {
            return response($exception->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(BookingRequest $request)
    {
        try {
            $adminSenderEmail = config('app.adminemail');
            $data = $request->validated();

            $response = $this->repository->storeJobEmail($data);

            return response($response);
        }catch (\Exception $exception) {
            return response($exception->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(BookingRequest $request)
    {
        // code should be in try catch block

        if($user_id = $request->get('user_id')) {
            // user_id should be verified
            $user = User::find($user_id);
            if ($user) {
                $response = $this->repository->getUsersJobsHistory($user->id, $request->validated());
                return response($response);
            }
        }
        // Response pattern should be same all over the application to avoid property binding
        return response([]);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(BookingRequest $request)
    {
        // code should be in try catch block

        $data = $request->validated();
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJob($data, $user);

        return response($response);
    }

    public function acceptJobWithId(BookingRequest $request)
    {
        // code should be in try catch block
        $data = $request->get('job_id');
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJobWithId($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(BookingRequest $request)
    {
        $data = $request->validated();
        $user = $request->__authenticatedUser;

        $response = $this->repository->cancelJobAjax($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(BookingRequest $request)
    {
        // code should be in try catch block

        $data = $request->validated();

        $response = $this->repository->endJob($data);

        return response($response);

    }

    public function customerNotCall(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->customerNotCall($data);

        return response($response);

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->getPotentialJobs($user);

        return response($response);
    }

    public function distanceFeed(DistanceFeedRequest $request)
    {
        $data = $request->validated();

        $distance = $data['distance'] ?? '';
        $time = $data['time'] ?? '';
        $jobId = $data['jobid'] ?? null;
        $sessionTime = $data['session_time'] ?? '';
        $adminComment = $data['admincomment'] ?? '';
        if ($data['flagged'] === 'true' && empty($adminComment)) {
            return response(['error' => 'Please add a comment']);
        }
        $flagged = ($data['flagged'] === 'true') ? 'yes' : 'no';
        $manuallyHandled = ($data['manually_handled'] === 'true') ? 'yes' : 'no';
        $byAdmin = ($data['by_admin'] === 'true') ? 'yes' : 'no';
        if ($jobId && ($time || $distance)) { // $jobId is necessary
            $affectedRows = Distance::where('job_id', '=', $jobId)->update(array('distance' => $distance, 'time' => $time));
        }

        if ($jobId && ($admincomment || $session || $flagged || $manually_handled || $by_admin)) {
            $affectedRows1 = Job::where('id', '=', $jobid)->update(array('admin_comments' => $admincomment, 'flagged' => $flagged, 'session_time' => $session, 'manually_handled' => $manually_handled, 'by_admin' => $by_admin));
        }
        return response('Record updated!');
    }

    public function reopen(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->reopen($data);

        return response($response);
    }

    public function resendNotifications(FormRequest $request)
    {
        $data = $request->validated();
        $job = $this->repository->find($data['jobid']);
        if ($job) {
            $job_data = $this->repository->jobToData($job);
            $this->repository->sendNotificationTranslator($job, $job_data, '*'); // Exclude user ids should be either managed dynamically or if they are static then should be managed in configurations to avoid static values in code
            return response(['success' => 'Push sent']);
        }
        return response([]);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        if ($job) {
            $job_data = $this->repository->jobToData($job);
            try {
                $this->repository->sendSMSNotificationToTranslator($job); // it doesn't return anything, there can be an error, response should be returned and based on that Success should be returned
                return response(['success' => 'SMS sent']); // proper translations should be used
            } catch (\Exception $e) {
                return response(['error' => $e->getMessage()]); // Exception message should be returned as error to identify the response type
            }
        }
    }
}
