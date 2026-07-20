<?php

    namespace App\Modules\EmployeeData;

    use App\Core\Controller;
    use App\Core\Request;
    use App\Core\Response;

    class PositionController extends Controller {
        private PositionModel $model;

        public function __construct() {
            $this->model = new PositionModel();
        }

        /** GET /api/positions */
        public function index(Request $request): void {
            $departmentId = $request->input('department_id');
            $rows = $departmentId
                ? $this->model->where(['department_id' => $departmentId], ['title ASC'])
                : $this->model->all(['title ASC']);
            Response::json($rows);
        }

        /** POST /api/positions */
        public function store(Request $request): void {
            $data = $this->validated($request, [
                'title' => 'required|string|max:150',
                'department_id' => 'required|integer',
            ]);
            Response::json($this->model->create($data), 201);
        }

        /** PUT /api/positions/{id} */
        public function update(Request $request): void {
            $id = (int) $request->param('id');
            if (!$this->model->find($id)) {
                Response::error('Position not found.', 404);
            }
            $data = $this->validated($request, [
                'title' => 'nullable|string|max:150',
                'department_id' => 'nullable|integer',
            ]);
            Response::json($this->model->update($id, array_filter($data, fn($v) => $v !== null)));
        }

        /** DELETE /api/positions/{id} */
        public function destroy(Request $request): void {
            $id = (int) $request->param('id');
            if (!$this->model->find($id)) {
                Response::error('Position not found.', 404);
            }
            $this->model->delete($id);
            Response::noContent();
        }
    }
