<?php //    this is the content for the UserHome page.
$rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);
require_once $rootDir .  '/classes/model/UserAccount.php';
require_once $rootDir .  '/classes/view/View.php';
require_once $rootDir . '/classes/controller/UserController.php';
class BillsView extends View {
    
    private $billsTemplateHead = '<div class="content-bill"><div class="content-row"><div class="past-bills"<h2>All Bills</h2>';
    private $billsTemplateTail = '</div></div></div>';    
    
    protected function printUnauthenticatedHeader() {
        header("Location: " . View::UNAUTHORIZED_PAGE);
    }
    
    function __construct() {
        $this->clientController = new UserController();
        $this->permissions = $this->clientController->getPermissions();
    }
    
    private function printBills() {
        $bills = $this->clientController->getActiveAccount()->getBills();

        foreach($bills as $bill) {
            $pastBillEntry = '<div class="past-entry">
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
                        <div class="btn-column">' 
                            . (!$bill->getIsPaid() ? '<a href="/user/payBill.php"><div class="btn-pay"><p>Pay Bill</p></div></a>' : '<a href="/user/disputeBill.php?bill=' . $billID . '"><div class="btn-pay"><p>Dispute Bill</p></div></a>') . '                            
                        </div>
                    </div>';
            
            echo $pastBillEntry;
        }
    }

    protected function printUserBody() {
        echo $this->billsTemplateHead;
        $this->printBills();
        echo $this->billsTemplateTail;
    }
    
    protected function printAdminBody() {
        ;
    }
    
    protected function printUnauthenticatedBody() {
        ;
    }
}

$billsView = new BillsView();
$billsView->renderPage();