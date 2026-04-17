<?php
/**
 * DashboardController — main landing page after login
 */
class DashboardController extends Controller
{
    /** Show the dashboard */
    public function index(): void
    {
        Auth::requireLogin();

        $data = [
            'page_title' => 'Dashboard',
        ];

        $this->render('dashboard/index', $data);
    }
}
