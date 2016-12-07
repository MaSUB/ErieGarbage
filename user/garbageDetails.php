<?php //    this is the content for the UserHome page.
$rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);
require_once $rootDir . '/classes/view/Header.php';
require_once $rootDir . '/classes/view/View.php';
require_once $rootDir . '/classes/controller/UserController.php';

class GarbageDetailsView extends View {
    
    private $garbageDetails = ""; // html code for garbage details
    
    function __construct() {           
        // Set default timezone
        date_default_timezone_set('America/New_York');
        
        // Initialize controller
        $this->clientController = new UserController();
        $this->permissions = $this->clientController->getPermissions();
        
        // Only users are allowed to view
        if (!($this->permissions === DatabaseController::ACTIVE_USER_PERMISSION()))
            header('Location: /unauthorized.php');
    }
    
    protected function printUserHeader() {
        echo Header::USER_HEADER_LOGGED_IN_CALENDAR;
    }
    
    protected function printUnauthenticatedHeader() {
        header("Location: " . View::UNAUTHORIZED_PAGE);
    }
    
    protected function printUserBody() {
        // Output surrounding divs
        echo '<div class="content-home"><div class="content-row">';
        // Output date header for calendar
        echo '<h1>' . date('F') . ' ' . date('Y') . '</h1><br>';
        // Draw the calendar
        echo $this->draw_calendar(date('n'), date('Y'));
        // Output closing divs
        echo "</div></div>";
    }
    
    protected function printAdminBody() {
        header('Location: ' . View::UNAUTHORIZED_PAGE); // Admins should not have access to this functionality
    }
    
    protected function printUnauthenticatedBody() {
        header('Location: ' . View::LOGIN_PAGE);
    }
    
    /* draws a calendar */
    private function draw_calendar($month,$year){
        $success = false;
        
        // Read pickup times file
        $pickupTimes = $this->clientController->loadPickupTimes();
        
        // Make sure file was read properly
        if (!($pickupTimes === null)) {  

            /* draw table */
            $calendar = '<table cellpadding="0" cellspacing="0" class="calendar">';

            /* table headings */
            $headings = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
            $calendar.= '<tr class="calendar-row"><td class="calendar-day-head">'.implode('</td><td class="calendar-day-head">',$headings).'</td></tr>';

            /* days and weeks vars now ... */
            $running_day = date('w',mktime(0,0,0,$month,1,$year));
            $days_in_month = date('t',mktime(0,0,0,$month,1,$year));
            $days_in_this_week = 1;
            $day_counter = 0;
            $dates_array = array();

            /* row for week one */
            $calendar.= '<tr class="calendar-row">';

            /* print "blank" days until the first of the current week */
            for($x = 0; $x < $running_day; $x++):
                $calendar.= '<td class="calendar-day-np"> </td>';
                $days_in_this_week++;
            endfor;

            /* keep going with days.... */
            for($list_day = 1; $list_day <= $days_in_month; $list_day++):
                $calendar.= '<td class="calendar-day">';
                    /* add in the day number */
                    $calendar.= '<div class="day-number">'.$list_day.'</div>';

                    /** QUERY THE DATABASE FOR AN ENTRY FOR THIS DAY !!  IF MATCHES FOUND, PRINT THEM !! **/
                    if ($pickupTimes[$list_day] === 'true') {
                        $calendar.= '<p class="calendar-text">Pickup at 8am</p>';
                    }

                    $calendar.= str_repeat('<p> </p>',2);

                $calendar.= '</td>';
                if($running_day == 6):
                    $calendar.= '</tr>';
                    if(($day_counter+1) != $days_in_month):
                        $calendar.= '<tr class="calendar-row">';
                    endif;
                    $running_day = -1;
                    $days_in_this_week = 0;
                endif;
                $days_in_this_week++; $running_day++; $day_counter++;
            endfor;

            /* finish the rest of the days in the week */
            if($days_in_this_week < 8):
                for($x = 1; $x <= (8 - $days_in_this_week); $x++):
                    $calendar.= '<td class="calendar-day-np"> </td>';
                endfor;
            endif;

            /* final row */
            $calendar.= '</tr>';

            /* end the table */
            $calendar.= '</table>';

            /* all done, return result */
            return $calendar;
            
        } else
            throw new Exception("Pickup times are null");
    }
}

$garbageDetailsView = new GarbageDetailsView();
$garbageDetailsView->renderPage();


