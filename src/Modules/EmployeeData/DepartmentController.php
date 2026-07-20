<?php

namespace App\Modules\EmployeeData;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

class DepartmentController extends Controller
{
    private DepartmentModel $model;

    public function __construct()
    {
        $this->model = new DepartmentModel();
    }

    /** GET /api/departments */
    public function index(Request $request): void
    {
        Response::json($this->model->all(['name ASC']));
    }

    /** GET /api/departments/{id} */
    public function show(Request $request): void
    {
        $department = $this->model->find((int) $request->param('id'));
        if (!$department) {
            Response::error('Department not found.', 404);
        }
        Response::json($department);
    }

    /** POST /api/departments */
    public function store(Request $request): void
    {
        $data = $this->validated($request, [
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:20',
            'parent_department_id' => 'nullable|integer',
            'manager_employee_id' => 'nullable|integer',
        ]);
        Response::json($this->model->create($data), 201);
    }

    /** PUT /api/departments/{id} */
    public function update(Request $request): void
    {
        $id = (int) $request->param('id');
        if (!$this->model->find($id)) {
            Response::error('Department not found.', 404);
        }
        $data = $this->validated($request, [
            'name' => 'nullable|string|max:150',
            'code' => 'nullable|string|max:20',
            'parent_department_id' => 'nullable|integer',
            'manager_employee_id' => 'nullable|integer',
        ]);
        Response::json($this->model->update($id, array_filter($data, fn($v) => $v !== null)));
    }

    /** DELETE /api/departments/{id} */
    public function destroy(Request $request): void
    {
        $id = (int) $request->param('id');
        if (!$this->model->find($id)) {
            Response::error('Department not found.', 404);
        }
        $this->model->delete($id);
        Response::noContent();
    }
}
