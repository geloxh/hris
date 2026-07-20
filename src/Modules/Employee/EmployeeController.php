<?php

    namespace App\Modules\Employee;

    use App\Core\Controller;
    use App\Core\Request;
    use App\Core\Response;
    use App\Core\Validator;

    class EmployeeController extends Controller {
        private EmployeeModel $model;
        private EmployeeService $service;

        public function __construct() {
            $this->model = new EmployeeModel();
            $this->service = new EmployeeService();
        }

        /** GET /api/employees */
        public function index(Request $request): void {
            $page = (int) $request->input('page', 1);
            $perPage = (int) $request->input('per_page', 20);

            $result = $this->model->searchPaginate([
                'department_id' => $request->input('department_id'),
                'employment_status' => $request->input('employment_status'),
                'q' => $request->input('q'),
            ], $page, $perPage);

            Response::json($result['data'], 200, $result['meta']);
        }

        /** GET /api/employees/{id} */
        public function show(Request $request): void {
            $employee = $this->model->withRelations((int) $request->param('id'));
            if (!$employee) {
                Response::error('Employee not found.', 404);
            }
            Response::json($employee);
        }

        /** POST /api/employees */
        public function store(Request $request): void {
            $data = $this->validated($request, [
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'middle_name' => 'nullable|string|max:100',
                'email' => 'required|email|max:150',
                'phone' => 'nullable|string|max:30',
                'birth_date' => 'nullable|date',
                'gender' => 'nullable|in:male,female,other,undisclosed',
                'hire_date' => 'required|date',
                'employment_type' => 'required|in:full-time,part-time,contract,intern',
                'department_id' => 'nullable|integer',
                'position_id' => 'nullable|integer',
                'manager_id' => 'nullable|integer',
                'address' => 'nullable|string',
                'emergency_contact_name' => 'nullable|string|max:150',
                'emergency_contact_phone' => 'nullable|string|max:30',
            ]);

            // optional: create a self-service login for this employee at the same time
            $accountData = null;
            if ($request->input('create_account')) {
                $accountData = Validator::validate($request->body, [
                    'account_email' => 'nullable|email',
                    'account_password' => 'required|string|min:8',
                    'role_id' => 'required|integer',
                ]);
                $accountData = [
                    'email' => $accountData['account_email'] ?? null,
                    'password' => $accountData['account_password'],
                    'role_id' => $accountData['role_id'],
                ];
            }

            $employee = $this->service->onboard($data, $accountData);
            Response::json($employee, 201);
        }

        /** PUT/PATCH /api/employees/{id} */
        public function update(Request $request): void {
            $id = (int) $request->param('id');
            if (!$this->model->find($id)) {
                Response::error('Employee not found.', 404);
            }

            $data = $this->validated($request, [
                'first_name' => 'nullable|string|max:100',
                'last_name' => 'nullable|string|max:100',
                'middle_name' => 'nullable|string|max:100',
                'email' => 'nullable|email|max:150',
                'phone' => 'nullable|string|max:30',
                'birth_date' => 'nullable|date',
                'gender' => 'nullable|in:male,female,other,undisclosed',
                'employment_status' => 'nullable|in:active,inactive,terminated,on-leave',
                'employment_type' => 'nullable|in:full-time,part-time,contract,intern',
                'department_id' => 'nullable|integer',
                'position_id' => 'nullable|integer',
                'manager_id' => 'nullable|integer',
                'address' => 'nullable|string',
                'emergency_contact_name' => 'nullable|string|max:150',
                'emergency_contact_phone' => 'nullable|string|max:30',
            ]);

            // drop nulls so PATCH-style partial updates don't blank out untouched columns
            $data = array_filter($data, fn($v) => $v !== null);

            $employee = $this->model->update($id, $data);
            Response::json($employee);
        }

        /** DELETE /api/employees/{id} - soft delete */
        public function destroy(Request $request): void {
            $id = (int) $request->param('id');
            if (!$this->model->find($id)) {
                Response::error('Employee not found.', 404);
            }
            $this->model->delete($id);
            Response::noContent();
        }

        /** POST /api/employees/{id}/terminate */
        public function terminate(Request $request): void {
            $id = (int) $request->param('id');
            $data = Validator::validate($request->body, [
                'last_working_date' => 'required|date',
            ]);

            if (!$this->model->find($id)) {
                Response::error('Employee not found.', 404);
            }

            $employee = $this->service->terminate($id, $data['last_working_date']);
            Response::json($employee);
        }
    }
