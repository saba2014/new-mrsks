{{ javascriptInclude("//maps.google.ru/maps/api/js?key=AIzaSyCRn2uTIizKmIS7dGJlBLSHaKLQv_4C7Og&v=3&amp") }}
{{ javascriptInclude("//api-maps.yandex.ru/2.1/?lang=ru_RU") }}


<script>
    var sMarker, legendnabled = 1;
    var popup = L.popup();
    var s_admin = {{ admin }};
    var myRole =0;
    var stopMap = 0;
    var icons = "{{ icons }}";
    var jsbaseurl = window.location.host;
    var mask = {{ mask_json }};
    var univers_mask = {{ univers_mask_json }};
    var terr = {{ tree_territory }};
    var adminenabled = {{ admin }};
    var minimapenabled = $.cookie('minimapflag') === undefined ? 1 : $.cookie('minimapflag');
    var legendenabled = $.cookie('legendflag') === undefined ? 1 : $.cookie('legendflag');
    var coord_1 = {{ coord_1 }};
    var coord_2 = {{ coord_2 }};
    var zoom = {{ zoom }};
    var index_handler = new Index_handler_functions();
    var menu = {{ menu }};
    var role = "{{ role }}";
 </script>

<body ng-app="myApp" ng-controller="MapCtrl">
{{ partial("shared/message", {}) }}
<div id="spin" class="dontprint"></div>

<div ng-class="kingdomClass" class="mobileMargin" ng-show="kingdomSh && !kingdomSm" id="kingdom">
    <div ng-controller="KingdomCtrl">
        <div class="container flex-wrap card menuItemText" style="pointer-events: auto;">
            <div class="row">
                <div class="col-sm-12 trainingHeaderPanel">
                    Режим тренировки
                </div>
            </div>
            <res>
            </res>
        </div>
        <div class=" col-md-12 col-12 padding-0 card trainingPanel" style="pointer-events: auto;">
            <footer>
                <menu class="container row w-100 padding-0" ng-show="shArmies"></menu>
            </footer>
        </div>
    </div>
</div>

<div id="p_Top" class="dontprint visible px-0 mx-0">
    <div class="form-group row text-nowrap">
        <div class="col-lg-12 p-0">
            <form action="" method="post" class="form-horizontal">
            <div class="input-group">
                <div class="input-group-prepend show m-1">

                    <button id="searchDropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split d-sm-block d-md-none" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button>

                    <div class="dropdown-menu" x-placement="bottom-end" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(-1px, 35px, 0px);">
                        <div class="dropdown-item d-sm-block d-md-none px-0">
                            <input type="button" class="admin_tgl_button dropdown-item d-sm-block d-md-none" onclick="index_handler.adminbutton();$('#searchDropdown').dropdown('toggle')" value="Администрирование"/>
                        </div>

                        <div class="dropdown-item d-sm-block d-md-none px-1">
                            <label><input type="checkbox" ng-click="kingdom()" onclick="$('#searchDropdown').dropdown('toggle')"> Тренировка</label>
                        </div>
                        <div class="dropdown-item d-sm-block d-md-none px-0">
                            <input type="button" class="dropdown-item d-sm-block d-md-none" onclick="index_handler.s_Logout();$('#searchDropdown').dropdown('toggle')" value="Выход"/>
                        </div>
                    </div>
                </div>

                <select name="s_Type" id="s_Select" class="form-control m-1" onchange="index_handler.changeSearchType(self);">
                    <option value="nominatim">Поиск по адресу</option>
                    <option value="rucadastrText">Кадастровое описание</option>
                    <option value="rucadastr_new">Кадастровый номер</option>
                    <option value="latlonSearch">Координаты</option>
                    <option value="tplnr">Поиск по коду техместа</option>
                    <option value="denotation">Поиск по наименованию объекта</option>
                    <option value="res">Поиск по наименованию РЭС</option>
                    <option value="worker" id="numSearch">Поиск сотрудника
                    </option>
                </select>
                <select name="s_Region" id="s_RegList" class="form-control m-1" >
                <option value="0">... Выберите регион ...</option>
                {{ sel_cont }}
                </select>

                <input type="search" class="form-control m-1" id="s_Input" required results=5 autofocus placeholder="пример: Красноярск Ленина 32"/>
                <button type="button" class="btn btn-primary m-1 px-2 py-1" onclick="getObjs()">
                    <!--<i class="fa fa-search"></i>-->
                    <div class="searchButton">
                    {{ types["Search"] }}
                    </div>
                </button>

                <div class="d-none d-md-inline-block my-auto">
                    <span>Тренировка</span>
                    <input type="checkbox" ng-click="kingdom()"/>
                </div>
                <button type="button" class="minimapbutton btn btn-primary btn-light custom-btn m-1 d-none d-md-block d-xl-block" onclick="index_handler.minimapbutton(minimap)">Миникарта</button>
                <button type="button" class="legendbutton btn btn-primary btn-light custom-btn m-1 d-none d-md-block d-xl-block" onclick="index_handler.legendbutton(legendnabled)">Легенда</button>
                <button type="button" class="admin_button btn btn-primary btn-light custom-btn m-1 d-none" onclick="index_handler.adminbutton()">
                    <!--<i class="fa fa-cogs d-sm-block d-md-block d-xl-none"></i><span class="d-none d-md-none d-xl-block">Администрирование</span>-->
                    <img src="css/images/icon-cogs.svg" class="d-sm-block d-md-block d-xl-none"><span class="d-none d-md-none d-xl-block">Администрирование</span>
                </button>
                <button type="button" class="btn btn-primary btn-light custom-btn m-1 px-2 py-1" onclick="index_handler.s_Logout()">
                    <!--<i class="fa fa-close d-sm-block d-md-block d-xl-none"></i><span class="d-none d-md-none d-xl-block">Выход</span>-->
                    <img src="css/images/icon-close-24px.svg" class="d-sm-block d-md-block d-xl-none"><span class="d-none d-md-none d-xl-block">Выход</span>
                </button>
            </div>
            </form>
        </div>
    </div>
</div>


<div id="p_Result" class="dontprint" ng-controller="ResultController">
    <a id="s_Close" href="#" onclick="index_handler.s_Close_result(cont.search);">×</a>
    <div id="p_Resul_info">Результаты
        <hr/>
    </div>
    <li dir-paginate="item in items  | itemsPerPage: itemPerPage track by $index " total-items="item_count"
        current-page="page" pagination-id="collection">
        <span ng-bind-html="item | unsafe"></span>
    </li>


    <div id="pagination">
        <dir-pagination-controls on-page-change="pageChanged(newPageNumber)"
                                 pagination-id="collection"></dir-pagination-controls>
    </div>
</div>
<div id="p_Route" class="hidden">
    <div id="routeSpinner" class="dontprint"></div>
    <a href="#" id="closeRoute" onclick="drawPath.close_result()">[X]</a>
    <div>
        <img src="img/icons/green-icon.png">
        <div id="routeFrom">Из</div>
    </div>
    <br>
    <div>
        <img src="img/icons/red-icon.png">
        <div id="routeTo">В</div>
    </div>
    <hr>
    <div id="routeContent">
    </div>
</div>
<div id="p_Report">
    <a id="s_Close" href="#" onclick="index_handler.s_Close_report();">×</a>
    <div id="s_Close_info">Подготовленные данные
        <hr/>
    </div>
    <div id="text">
        <div id="dist"></div>
        <div id="uObject"></div>
        <div id="uLine"></div>
        <div id="uPStation"></div>
        <div id="uPosToBounds">
            <!--input type="button" value="Сохранить" onclick=""-->
            <input class="dontprint" type="button" value="Очистить" onclick="index_handler.cleanReport();">
            <input class="dontprint" type="button" value="Печать" onclick="index_handler.sPrintIt();">
        </div>
    </div>
</div>
<div id='p_Workers' ng-class="style" style="display: none" ng-controller="WorkersController">
    <div>
	        <div id="turnFilter" class="leaflet-control-distance" ng-click="setStyle()" style="margin-bottom: 0px">
            <ngimg></ngimg>
        </div>

        <h4  style="text-align: center" class="inner-content">Фильтр</h4>
        <div id="z">
        <div id ="form-control-content">
        <div class="form-group row justify-content-between inner-content">

            <div class="col-10">
               С <input class="form-control inner-content" type="date" ng-model='first_date'>
        </div>

        <div class="form-group row justify-content-between inner-content">
               <div class="col-10">
               По <input class="form-control" type="date" ng-model='last_date'>
            </div>
        </div>
        <div class="form-group row inner-content justify-content-between">
            <div class="form-control-add-block">
            <input ng-model='input' id="add_worker" type="search" class="form-control"
                   placeholder="Введите фамилию" uib-typeahead="worker for worker in getUsersByName($viewValue)">
                   <!--uib-typeahead="worker for worker in allWorkers | filter:$viewValue | limitTo:5">-->
            <button id="btn-delete" class="btn btn-outline-primary col-2" ng-click="clear()">
                <img src="css/images/close.png" alt="Del">

            </button>
            </div>
            <button id="btn-add" class="btn btn-outline-primary col-2" ng-click="add()" >
                <img src="css/images/bt_add.svg" alt="Add">
            </button>
        </div>
        <div ng-repeat='man in people' style="margin-bottom: 7px" class="inner-content justify-content-between">
            <outputPeople></outputPeople>
            <button  class="btn btn-outline-primary align-self-end new-delete-class" ng-click='delete(man)'>
            <img src="css/images/close.png" alt="Del">

            </button>
       </div>
        <div id="reset-block">
            <button  class="btn-reset" id="btn-reset" type="reset"  ng-click="clear()" >Сброс</button>
        </div>
      </div>
	      </div>
	    </div>

	</div>
</div>
<div id="p_Map" style="overflow:hidden;"></div>
<div id="minimap-window" class="draggable ui-draggable dontprint" >
    <div id="dhead">
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td width="1">
                    <span class="headfont">#</span>
                </td>
                <td height="22" width="99%" align="center">
                    <span class="headfont">Миникарта</span>
                </td>
                <td width="1">
                    <a href="" onclick="index_handler.minimap_hide(minimapenabled)">
                        <span class="headfont">[X]</span>
                    </a>
                </td>
            </tr>
        </table>
    </div>
    <div id="minimap"></div>
</div>
<div id="legend-window" class="dontprint">
    <div id="dlegendhead">
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td width="1">
                    <span class="headfont">#</span>
                </td>
                <td height="22" width="99%" align="center">
                    <span class="headfont">Легенда карты</span>
                </td>
                <td width="1">
                    <a href="#" onclick="index_handler.legend_hide(legendenabled); return false;">
                        <span class="headfont">[X]</span>
                    </a>
                </td>
            </tr>
        </table>
    </div>
    <div id="legend">
        <a href="#" onClick="index_handler.toggle('#TLEP');">
            <span id="TLEP_on"> ►</span>
            <span id="TLEP_off"> ▼</span><b>Линии ЭП</b></a><br>
        <div id="TLEP">
            {{ types["line"] }}
        </div>

        <a href="#" onClick="index_handler.toggle('#RP');">
            <span id="RP_on"> ►</span>
            <span id="RP_off"> ▼</span>
            <b>РП</b></a><br>
        <div id="RP">
            {{ types["rp"] }}
        </div>

        <a href="#" onClick="index_handler.toggle('#TP');">
            <span id="TP_on"> ►</span>
            <span id="TP_off"> ▼</span>
            <b>ТП</b></a><br>
        <div id="TP">
            {{ types["tp"] }}
        </div>

        <a href="#" onClick="index_handler.toggle('#TPS');">
            <span id="TPS_on">►</span>
            <span id="TPS_off">▼</span><b>ПС</b></a><br>
        <div id="TPS">
            {{ types["ps"] }}
        </div>
        <a href="#" onClick="index_handler.toggle('#training');">
                    <span id="training_on">►</span>
                    <span id="training_off">▼</span><b>Модуль тренировки</b></a><br>
                <div id="training">
                <div class="row">
                    <div class="col-2 training">
                        {{ types["RISE"] }}
                    </div>
                    <div class="col-2">РИСЭ</div>
                </div>
                <div class="row">
                    <div class="col-2 training">
                        {{ types["Emergency"] }}
                    </div>
                    <div class="col-8">Аварийный резерв</div>
                </div>
                <div class="row">
                    <div class="col-2 training">
                        {{ types["Center"] }}
                    </div>
                    <div class="col-8">Центр ПО/РЭС</div>
                </div>
                </div>
        <a href="#" onClick="index_handler.toggle('#OTHER');">
            <span id="OTHER_on"> ►</span>
            <span id="OTHER_off"> ▼</span>
            <b>Заявители</b></a><br>
        <table id="OTHER" align="center" width="90%" cellpadding="0" cellspacing="2">
            <tr>
                <td>
                    {{ types["applicant_1"] }}
                </td>
                <td>Заявители этого года</td>
            </tr>
            <tr>
                <td>
                    {{ types["applicant_2"] }}
                </td>
                <td>Заявители прошлого года</td>
            </tr>
            <tr>
                <td>
                    {{ types["applicant_3"] }}
                </td>
                <td>Заявители прошлых лет</td>
            </tr>
        </table>
    </div>
</div>

<div ng-show="LayersMenuSh" id="btnTrackContainer" class="LayersMenuStyle animated bounceInUp hidden">
    <input class="btn btn-info btn-sm btn-block" type="submit" id="HideTrack" value="скрыть трек"
           ng-click="DeleteLayers()">
</div>





<div id="footer_name">&nbsp;2014-2018&nbsp;&copy;&nbsp;ООО&nbsp;РЦ&nbsp;&copy;&nbsp;
    <a href="http://navikey.org/" target="Navikey">Navikey
    </a>&trade;
</div>
</body>
{{ assets.outputJs("js") }}
