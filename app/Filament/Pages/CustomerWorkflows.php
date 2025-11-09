<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Services\ZuoraService;
use Exception;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class CustomerWorkflows extends Page
{
    protected static ?string $slug                     = 'workflows/{customer}';
    protected static bool    $shouldRegisterNavigation = false;
    public string            $customer;
    public ?Customer         $customerModel            = null;
    public ?string           $error                    = null;
    public array             $workflows                = [];
    protected string         $view                     = 'filament.pages.customer-workflows';

    public function mount ( string $customer ) : void
    {
        $this -> customer      = $customer;
        $this -> customerModel = Customer ::where ( 'name', $customer ) -> first ();

        if ( !$this -> customerModel ) {
            abort ( 404, 'Customer not found' );
        }

        $this -> loadWorkflows ();
    }

    protected function loadWorkflows () : void
    {
        try {
            $service = new ZuoraService();
            $data    = $service -> listWorkflows (
                $this -> customerModel -> client_id,
                $this -> customerModel -> client_secret,
                $this -> customerModel -> base_url,
                1,
                50
            );

            $this -> workflows = $data[ 'data' ] ?? $data[ 'workflows' ] ?? [];
        } catch ( Exception $e ) {
            $this -> error     = $e -> getMessage ();
            $this -> workflows = [];
        }
    }

    public function getTitle () : string
    {
        return "Workflows - {$this->customer}";
    }

    public function getHeading () : string
    {
        return "Workflows for {$this->customer}";
    }

    public function downloadWorkflow ( string $workflowId )
    {
        try {
            $service  = new ZuoraService();
            $workflow = $service -> downloadWorkflow (
                $this -> customerModel -> client_id,
                $this -> customerModel -> client_secret,
                $this -> customerModel -> base_url,
                $workflowId
            );

            $fileName = "workflow_{$workflowId}.json";
            $content  = json_encode ( $workflow, JSON_PRETTY_PRINT );

            return response () -> streamDownload ( function () use ( $content ) {
                echo $content;
            }, $fileName, [
                'Content-Type' => 'application/json',
            ] );
        } catch ( Exception $e ) {
            Notification ::make ()
                         -> danger ()
                         -> title ( 'Download Failed' )
                         -> body ( "Error downloading workflow: " . $e -> getMessage () )
                         -> send ();
        }
    }
}
