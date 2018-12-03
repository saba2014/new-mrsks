<div class="container-fluid align-items-lg-center" id="main" ng-controller="TabCtrl">
<script>
var cont;
</script>

{{ partial("shared/message", {}) }}
<!--<div class="row">-->
        <div class="jumbotron" id="title">
            <h2 class="animated fadeInLeft text-right">Консоль Администратора системы <a href="../">Геомодуль МРСК</a></h2>
                <p class="lead text-right animated fadeInRight" id="tip"> для пользователей ПАО "МРСК Сибири"</p>
        </div>
<!--</div>-->
    {{ partial("shared/electric_modal", {}) }}
    {{ partial("shared/workers_modal", {}) }}
    {{ partial("shared/univers_modal", {}) }}
    {{ partial("shared/workers_modal", {}) }}
    {{ partial("shared/ovb_modal", {}) }}
    {{ partial("shared/ovb_add_modal", {}) }}
    {{ partial("shared/add_universe_objecte", {}) }}
    {{ partial("shared/res_modal", {}) }}
    {{ partial("shared/universWays_modal", {}) }}




    <nav class="navbar navbar-expand-lg navbar-light bg-light">
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNavDropdown">
        <ul class="navbar-nav">
            <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Объекты электросети
                        </a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                <a class="dropdown-item" href="#" ng-click="selectTab(1)">Опора</a>
                                <a class="dropdown-item" href="#" ng-click="selectTab(2)">Линия</a>
                                <a class="dropdown-item" href="#" ng-click="selectTab(3)">Подстанция</a>
                        </div>
            </li>
            <li ng-style="CurrStyle(4)" class="nav-item"><a class="nav-link" href ng-click="selectTab(4)">Заявитель</a></li>
            <li ng-style="CurrStyle(5)" class="nav-item"><a class="nav-link" href ng-click="selectTab(5)">Массовое удаление</a></li>
            <li ng-style="CurrStyle(6)" class="nav-item"><a class="nav-link" href ng-click="selectTab(6)">Загрузка данных</a></li>
            <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="http://example.com" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Мониторинг
                            </a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                <a class="dropdown-item" href="#" ng-click="selectTab(21)">Персонал</a>
                                <a class="dropdown-item" href="#" ng-click="selectTab(22)">Группы</a>
                        </div>
            </li>
                <li ng-class="CurrStyle(7)" class="nav-item"><a class="nav-link" href ng-click="selectTab(7)">Расписание обновлений</a></li>

            <li class="nav-item dropdown" id="last">
                                    <a class="nav-link dropdown-toggle" href id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    События
                                        </a>
                                    <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                                            <a class="dropdown-item" href="#" ng-click="selectTab(23)">Срочные сообщения</a>
                                    </div>
            </li>
            <li class="nav-item dropdown" id="UniversObjects">
                <a class="nav-link dropdown-toggle" href id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Универсиада 2019
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                    <a class="dropdown-item" href="#" ng-click="selectTab(8)">Объекты универсиады</a>
                    <a class="dropdown-item" href="#" ng-click="selectTab(10)">Маршруты Универсиады</a>
                </div>
            </li>
            <li ng-style="CurrStyle(9)" class="nav-item"><a class="nav-link" href ng-click="selectTab(9)">Настройка РЭС</a></li>


        </ul>
    </div>
</nav>


    <!--<div class="row"><p></p></div>-->
    <div class="row">
        <div ng-show="isSelected(1)" class="col-md-12 px-0 pt-2">
            {{ partial("shared/opora", {}) }}            
        </div>
        <div ng-show="isSelected(2)" class="col-md-12 px-0 pt-2">
            {{ partial("shared/line", {}) }}
        </div>
        <div ng-show="isSelected(3)" class="col-md-12 px-0 pt-2">
            {{ partial("shared/ps", {}) }}
        </div>
        <div ng-show="isSelected(4)" class="col-md-12 px-0 pt-2">
            {{ partial("shared/ztp", {}) }}
        </div>
        <div ng-show="isSelected(5)" class="col-md-12 px-0 pt-2">
            {{ partial("shared/delmass", {}) }}
        </div>
        <div ng-show="isSelected(6)" class="col-md-12 px-0 pt-2">
            {{ partial("shared/upload", {}) }}
        </div>
        <div ng-show="isSelected(7)" class="col-md-12 px-0 pt-2">
            {{ partial("shared/Schedule_update", {}) }}
        </div>
        <div ng-show="isSelected(8)" class="col-md-12 px-0 pt-2">
            {{ partial("shared/univers_objects", {}) }}
        </div>
        <div ng-show="isSelected(9)" class="col-md-12">
            {{ partial("shared/resConfiguration", {}) }}
        </div>
        <div ng-show="isSelected(10)" class="col-md-12">
            {{ partial("shared/UniversWays", {}) }}
        </div>
        <div ng-show="isSelected(21)" class="col-md-12">
            {{ partial("shared/workers", {}) }}
        </div>
        <div ng-show="isSelected(23)" class="col-md-12 px-0 pt-2">
                    {{ partial("shared/important_messages", {}) }}
        </div>
        <div ng-show="isSelected(22)" class="col-md-12 px-0 pt-2">
            {{ partial("shared/ovb", {}) }}
        </div>
    </div>
</div>