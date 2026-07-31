<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\View\View;
use App\Services\WorkflowService;
use Exception;

class WorkflowController extends Controller
{
    private WorkflowService $service;

    public function __construct(View $view, WorkflowService $service)
    {
        parent::__construct($view);
        $this->service = $service;
    }

    // --- ADMIN BACKOFFICE ACTIONS ---

    public function index(Request $request, Response $response)
    {
        $workflows = $this->service->listWorkflows();
        return $this->render('admin/workflows/index', [
            'workflows' => $workflows
        ]);
    }

    public function create(Request $request, Response $response)
    {
        return $this->render('admin/workflows/create');
    }

    public function store(Request $request, Response $response)
    {
        try {
            $data = $request->getBody();
            $workflowId = $this->service->createWorkflow($data);
            
            // Save trigger and action configurations
            $this->service->saveTrigger($workflowId, $data['event_name'] ?? 'order_created', $data['conditions'] ?? []);
            
            if (!empty($data['actions']) && is_array($data['actions'])) {
                foreach ($data['actions'] as $idx => $act) {
                    $this->service->addAction($workflowId, $act['type'], $act['config'] ?? [], $idx);
                }
            }
            
            return $response->redirect(url("/admin/workflows?success=1"));
        } catch (Exception $e) {
            return $this->render('admin/workflows/create', ['error' => $e->getMessage()]);
        }
    }

    public function edit(Request $request, Response $response)
    {
        $id = (int)$request->getRouteParam('id');
        $workflow = $this->service->getWorkflow($id);
        if (!$workflow) {
            return $response->redirect(url('/admin/workflows?error=not_found'));
        }
        return $this->render('admin/workflows/edit', ['workflow' => $workflow]);
    }

    public function templates(Request $request, Response $response)
    {
        return $this->render('admin/workflows/templates');
    }

    public function history(Request $request, Response $response)
    {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        $history = $db->query("SELECT h.*, w.name as workflow_name FROM workflow_history h JOIN workflows w ON h.workflow_id = w.id ORDER BY h.id DESC LIMIT 100");
        return $this->render('admin/workflows/history', [
            'history' => $history
        ]);
    }

    public function logs(Request $request, Response $response)
    {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        $logs = $db->query("SELECT l.*, w.name as workflow_name FROM workflow_logs l JOIN workflows w ON l.workflow_id = w.id ORDER BY l.id DESC LIMIT 100");
        return $this->render('admin/workflows/logs', [
            'logs' => $logs
        ]);
    }

    // --- REST API ENDPOINTS ---

    public function apiList(Request $request, Response $response)
    {
        $workflows = $this->service->listWorkflows();
        return $response->json([
            'status' => 'success',
            'data' => $workflows
        ]);
    }

    public function apiShow(Request $request, Response $response)
    {
        $id = (int)$request->getRouteParam('id');
        $workflow = $this->service->getWorkflow($id);
        if (!$workflow) {
            return $response->json(['status' => 'error', 'message' => 'Workflow not found'], 404);
        }
        return $response->json([
            'status' => 'success',
            'data' => $workflow
        ]);
    }

    public function apiRun(Request $request, Response $response)
    {
        $id = (int)$request->get('workflow_id');
        $payload = $request->getBody();
        try {
            $execId = $this->service->runWorkflow($id, $payload);
            return $response->json([
                'status' => 'success',
                'execution_id' => $execId,
                'message' => 'Workflow executed successfully'
            ]);
        } catch (Exception $e) {
            return $response->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function apiHistory(Request $request, Response $response)
    {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        $history = $db->query("SELECT h.*, w.name as workflow_name FROM workflow_history h JOIN workflows w ON h.workflow_id = w.id ORDER BY h.id DESC");
        return $response->json([
            'status' => 'success',
            'data' => $history
        ]);
    }

    public function apiLogs(Request $request, Response $response)
    {
        $db = \Core\Application::getInstance()->getContainer()->get(\Core\Contracts\DatabaseInterface::class);
        $logs = $db->query("SELECT l.* FROM workflow_logs l ORDER BY l.id DESC");
        return $response->json([
            'status' => 'success',
            'data' => $logs
        ]);
    }

    public function apiTemplates(Request $request, Response $response)
    {
        $templates = [
            [
                'id' => 1,
                'name' => 'Yeni Sipariş Otomasyonu',
                'description' => 'Yeni sipariş geldiğinde fatura keser, SMS ve e-posta gönderir.',
                'trigger_type' => 'order_created'
            ],
            [
                'id' => 2,
                'name' => 'Sepet Terk Hatırlatıcı',
                'description' => 'Alışveriş sepetini terk eden kullanıcılara 1 saat sonra indirim kuponu tanımlar.',
                'trigger_type' => 'cart_abandoned'
            ]
        ];
        return $response->json([
            'status' => 'success',
            'data' => $templates
        ]);
    }
}
