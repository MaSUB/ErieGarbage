<?php
require_once 'classes/controller/DatabaseController.php';
require_once 'classes/view/Header.php';
require_once 'classes/view/View.php';

class HomeView extends View{
    
    function __construct() {
        parent::__construct();
    }
    
    
    // Abstract functions of View
    protected function printUserBody() {
        echo $this->userHome;
    }
    
    protected function printAdminBody() {
        echo $this->adminHome;
    }
    
    protected function printUnauthenticatedHeader() {
        header('Location: ' . View::LOGIN_PAGE);
    }
    
    protected function printUnauthenticatedBody() {
        header('Location: ' . View::LOGIN_PAGE);
    }
    
    // HTML ELEMENTS


    // Define userHome outline
    private $userHome = '<div class="content-home">
    <div class="content-row imprt-dates">
        <div class="section-title"><h2>Important Dates</h2></div>
        <div class="row-text">
            <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Nam nibh. Nunc varius facilisis eros. Sed erat. In in velit quis arcu ornare laoreet. Curabitur adipiscing luctus massa. Integer ut purus ac augue commodo commodo. Nunc nec mi eu justo tempor consectetuer. Etiam vitae nisl. In dignissim lacus ut ante. Cras elit lectus, bibendum a, adipiscing vitae, commodo et, dui. Ut tincidunt tortor. Donec nonummy, enim in lacinia pulvinar, velit tellus scelerisque augue, ac posuere libero urna eget neque. Cras ipsum. Vestibulum pretium, lectus nec venenatis volutpat, purus lectus ultrices risus, a condimentum risus mi et quam. Pellentesque auctor fringilla neque. Duis eu massa ut lorem iaculis vestibulum. Maecenas facilisis elit sed justo. Quisque volutpat malesuada velit.
        </div>
    </div>
    <div class="content-row home-man-of-month">
        <div class="section-title"><h2>Garbageman of the month</h2></div>
        <div class="row-img-left">
            <img src="/store/resources/my_headshot.jpg" alt="">
        </div>
        <div class="row-text">
            <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Nam nibh. Nunc varius facilisis eros. Sed erat. In in velit quis arcu ornare laoreet. Curabitur adipiscing luctus massa. Integer ut purus ac augue commodo commodo. Nunc nec mi eu justo tempor consectetuer. Etiam vitae nisl. In dignissim lacus ut ante. Cras elit lectus, bibendum a, adipiscing vitae, commodo et, dui. Ut tincidunt tortor. Donec nonummy, enim in lacinia pulvinar, velit tellus scelerisque augue, ac posuere libero urna eget neque. Cras ipsum. Vestibulum pretium, lectus nec venenatis volutpat, purus lectus ultrices risus, a condimentum risus mi et quam. Pellentesque auctor fringilla neque. Duis eu massa ut lorem iaculis vestibulum. Maecenas facilisis elit sed justo. Quisque volutpat malesuada velit.
        </div>
    </div>
</div>';
    
    private $adminHome = '<div class="content-home">
            <div class="content-row imprt-dates">
                <div class="section-title"><h2>Important Dates</h2></div>
                <div class="row-text">
                    <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Nam nibh. Nunc varius facilisis eros. Sed erat. In in velit quis arcu ornare laoreet. Curabitur adipiscing luctus massa. Integer ut purus ac augue commodo commodo. Nunc nec mi eu justo tempor consectetuer. Etiam vitae nisl. In dignissim lacus ut ante. Cras elit lectus, bibendum a, adipiscing vitae, commodo et, dui. Ut tincidunt tortor. Donec nonummy, enim in lacinia pulvinar, velit tellus scelerisque augue, ac posuere libero urna eget neque. Cras ipsum. Vestibulum pretium, lectus nec venenatis volutpat, purus lectus ultrices risus, a condimentum risus mi et quam. Pellentesque auctor fringilla neque. Duis eu massa ut lorem iaculis vestibulum. Maecenas facilisis elit sed justo. Quisque volutpat malesuada velit.
                </div>
            </div>
            <div class="content-row home-man-of-month">
                <div class="section-title"><h2>Garbageman of the month</h2></div>
                <div class="row-img-left">
                    <img src="/store/resources/my_headshot.jpg" alt="">
                </div>
                <div class="row-text">
                    <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Nam nibh. Nunc varius facilisis eros. Sed erat. In in velit quis arcu ornare laoreet. Curabitur adipiscing luctus massa. Integer ut purus ac augue commodo commodo. Nunc nec mi eu justo tempor consectetuer. Etiam vitae nisl. In dignissim lacus ut ante. Cras elit lectus, bibendum a, adipiscing vitae, commodo et, dui. Ut tincidunt tortor. Donec nonummy, enim in lacinia pulvinar, velit tellus scelerisque augue, ac posuere libero urna eget neque. Cras ipsum. Vestibulum pretium, lectus nec venenatis volutpat, purus lectus ultrices risus, a condimentum risus mi et quam. Pellentesque auctor fringilla neque. Duis eu massa ut lorem iaculis vestibulum. Maecenas facilisis elit sed justo. Quisque volutpat malesuada velit.
                </div>
            </div>
        </div>';
}

// Execution
$homeView = new HomeView();
$homeView->renderPage();

?>