<?php
$rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);
require_once $rootDir . '/classes/controller/DatabaseController.php';
require_once $rootDir . '/classes/view/Header.php';
require_once $rootDir .  '/classes/view/View.php';

class DuePaymentsView extends View{
    
    private $billsTemplateHead;
    private $billsTemplateTail;
    
    
    function __construct() {
        parent::__construct();
        
        
        if ($this->clientController->isAuthenticated()) {
            if ($this->permissions === DatabaseController::ACTIVE_ADMIN_PERMISSION()) {
                $this->billsTemplateHead = '<div class="content-bill"><div class="content-row"><div class="past-bills"<h2>All Bills</h2>';
                $this->billsTemplateTail = '</div></div></div>';
                
            } else {
                // Insufficient permissions
                header('Location: ' . View::UNAUTHORIZED_PAGE);
                exit();
            }
        } else {
            // Not logged in
            header('Location: ' . View::LOGIN_PAGE);
            exit();
        }
    }
    
    // HTML ELEMENTS
    
    protected function printUserBody() {
        header('Location: ' . View::UNAUTHORIZED_PAGE);
    }
    
    protected function printAdminBody() {
        echo $this->billsTemplateHead;
        $this->printDuePayments();
        echo $this->billsTemplateTail;
    }
    
    protected function printUnauthenticatedBody() {
        header('Location: ' . View::LOGIN_PAGE);
    }
    
    protected function printUnauthenticatedHeader() {
        header('Location: ' . View::LOGIN_PAGE);
    }
    
    private function printDuePayments() {
        $dueBills = $this->databaseController->getDuePayments();
        foreach($dueBills as $bill) {
            $billEntry = '<div class="past-entry">
                        <div class="name-column">
                            <div class="name-row">
                                <p>Month:</p>
                            </div>
                            <div class="name-row">
                                <p>Amount Due:</p>
                            </div>
                            <div class="name-row">
                                <p>Due Date:</p>
                            </div>
                            <div class="name-row">
                                <p>Amount Paid:</p>
                            </div>
                        </div>
                        <div class="result-column">
                            <div class="result-row">
                                <p>' . $bill->getMonth() . '</p>
                            </div>
                            <div class="result-row">
                                <p>$' . $bill->getAmount() . '</p>
                            </div>
                            <div class="result-row">
                                <p>' . $bill->getDueDate() . '</p>
                            </div>
                            <div class="result-row">
                                <p>$' . ($bill->getIsPaid() ? $bill->getAmount() : 0.00) . '</p>
                            </div>
                        </div>
                        <div class="btn-column"><a href="/user/disputeBill.php?bill=' . $bill->getBillID() . '"><div class="btn-pay"><p>Dispute Bill</p></div></a>                            
                        </div>
                    </div>';
            
            echo $billEntry;
        }
    }

    
}

// Execution
$duePaymentsView = new DuePaymentsView();
$duePaymentsView->renderPage();

?>