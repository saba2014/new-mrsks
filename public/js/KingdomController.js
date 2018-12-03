let deleteMe;

window.myApp.controller('KingdomCtrl', function ($scope, objects) {

    let resLayer;
    let poCenter, resCenter, poEmergency, resEmergency, poRise, resRise, resCluster;
    let layers = [];
    let first = 0;
    $scope.filials = [];


    $scope.$on('initKingdom', function () {
        $scope.init();
        $scope.filials = [];
        for (let fil in terr) {
            let obj = {
                name: fil,
                pos: []
            };
            let pos = terr[fil].po;
            for (let po in pos) {
                let newPo = {
                    name: po,
                    res: []
                };
                let ress = pos[po].res;
                for (let res in ress) {
                    let newRes = {
                        name: res,
                        id: ress[res].RES_id,
                        color: ress[res].color
                    }
                    newPo.res.push(newRes);
                }
                obj.pos.push(newPo);
            }
            $scope.filials.push(obj);
        }
    });

    $scope.$on('closeKingdom', function () {
        $scope.clearMap();
        $scope.shPo = false;
        $scope.shRes = false;
        $scope.shSquads = false;
        $scope.shHeroes = false;
        $scope.shRights = false;
        $scope.shArmies = true;
        $scope.shPoResources = false;
        $scope.shResResources = false;
        $scope.rightsSh = false;
        $scope.currFil = "";
        $scope.currPo = "";
        $scope.currRes = "";
        Object.keys(layers).map(function(objectKey, index) {
            var value = layers[objectKey];
            value.onLayerRemove();
        });
    });

    $scope.shPo = false;
    $scope.shRes = false;
    $scope.shArmies = true;
    $scope.shSquads = false;
    $scope.shHeroes = false;
    $scope.shRights = false;

    $scope.rightsSh = false;
    $scope.shRefresh = false;


    $scope.shPoResources = false;
    $scope.shResResources = false;

    $scope.poInfo;
    $scope.resInfoCar;
    $scope.resInfoMan;
    $scope.poInfoCar;
    $scope.poInfoMan;
    $scope.assignmentInfoCar;
    $scope.assignmentInfoMan;

    $scope.currFil;
    $scope.currPo;
    $scope.currRes;
    $scope.pos = [];
    $scope.ress = [];

    self = this;
    $scope.test = "test";
    $scope.list = [];
    $scope.currHero = [];
    $scope.currSquad;
    $scope.currElem;
    $scope.currHero.rights = [];

    $scope.squads = [];
    $scope.heroes = [];
    $scope.warning = new Hero({"name": "Авария", "count": -1, "iconPath": "/img/icons/lighting_small.png"});

    let numOfHero = -1;
    $scope.marLen = 0;
    $scope.markers = [];
    let markersInfo = [];

    let PoInfo = [];
    let ResInfo = [];

    let filHandler = function () {
        if (!$scope.currFil || $scope.currFil == 0) {
            $scope.shPo = false;
            $scope.shRes = false;
            return;
        }
        let name = $scope.currFil;
        for (let i = 0; i < $scope.filials.length; i++)
            if (name === $scope.filials[i].name)
                $scope.pos = $scope.filials[i].pos;
        $scope.closeAll($scope.menu);
        $scope.closeSquads();
        $scope.shResResources = false;
        $scope.shPoResources = false;
        $scope.shRes = false;
        $scope.shPo = true;
    };

    let poHandler = function () {
        if (!$scope.currPo || $scope.currPo == 0) {
            $scope.shRes = false;
            return;
        }
        let name = $scope.currPo;
        for (let i = 0; i < $scope.pos.length; i++)
            if (name === $scope.pos[i].name) {
                $scope.ress = $scope.pos[i].res;
            }
        $scope.closeAll($scope.menu);
        $scope.closeSquads();
        $scope.shResResources = false;
        $scope.shRes = true;
        $scope.shPoResources = true;
        $scope.loadPoResources();
        if ($scope.$digets !== undefined) {
            $scope.$digets();
        }
        delete poCenter;
        let poId = terr[$scope.currFil].po[$scope.currPo].self.composite_id;
        poCenter = new ResCenter(map, L, poId, "po");
        poEmergency = new EmergencyReserve(map, L, poId, "po");
        poRise = new Rise(map, L, poId, "po");
        layers.poCenter = poCenter;
        layers.poEmergency = poEmergency;
        layers.poRise = poRise;
        if(this.resCluster=== undefined){
            let clusterSegment = new Cluster_segment();
            this.resCluster =  clusterSegment.getResCluster();;
        }
        poCenter.Cluster = this.resCluster;
        poEmergency.Cluster = this.resCluster;
        poRise.Cluster = this.resCluster;
    }

    let resHandler = function () {
        if (!$scope.currRes || $scope.currRes == 0)
            return;
        let type = "Res";
        let params = "&res_id=" + $scope.currRes;
        $scope.loadResResources();
        if (resLayer !== undefined) {
            resLayer.onLayerRemove();
        }
        resLayer = new TrainingRes(map, L, $scope.currRes);
        layers.resLayer = resLayer;
        $scope.closeSquads();
        $scope.shArmies = true;
        delete resCenter;
        resCenter = new ResCenter(map, L, $scope.currRes, "res");
        resEmergency = new EmergencyReserve(map, L, $scope.currRes, "res");
        resRise = new Rise(map, L, $scope.currRes, "res");
        layers.resCenter = resCenter;
        layers.resEmergency = resEmergency;
        resRise.resEmergency = resEmergency;

        if(this.resCluster=== undefined){
            let clusterSegment = new Cluster_segment();
            this.resCluster =  clusterSegment.getResCluster();;
        }
        resCenter.Cluster = this.resCluster;
        resEmergency.Cluster = this.resCluster;
        resRise.Cluster = this.resCluster;

        Object.keys(layers).forEach(function(objectKey) {
            var value = layers[objectKey];
            value.box = false;
            value.onLayerAdd();
        });
        $scope.init();
    };

    let markersHandler = function (newVal, oldVal) {
        $scope.shRefresh = false;
        if (newVal !== oldVal && newVal > 0)
            $scope.shRefresh = true;
    };

    $scope.$watch('currFil', filHandler);
    $scope.$watch('currPo', poHandler);
    $scope.$watch('currRes', resHandler);
    $scope.$watch('marLen', markersHandler);


    $scope.menu = [];
    $scope.menu.length = 6;

    let setData = function (info) {
        let resources = info.resources;
        $scope.list = [];
        for (let i = 0; i < resources.length; i++) {
            let obj = new Hero(resources[i]);
            $scope.list.push(obj);
        }

        for (let i = 0; i < $scope.list.length; i++) {
            $scope.list[i]["available"] = $scope.list[i].amount;
            $scope.list[i]["style"] = "";
            $scope.list[i]["active"] = false;
            for (let j = 0; j < $scope.list[i].rights.length; j++) {
                $scope.list[i].rights[j]["active"] = true;
            }
        }
    };

    $scope.addMainElem = function () {
        let type = 'Staff';
        let params = "&typeStaff=assignment";
        objects.search(type, undefined, undefined, undefined, function (response) {
            let info = response.data.features;
            $scope.assignmentInfoMan = [];
            $scope.assignmentInfoCar = [];
            for (let i = 0; i < info.length; i++) {
                if (info[i].walk == true) {
                    $scope.assignmentInfoMan.push(info[i]);
                }
                else {
                    $scope.assignmentInfoCar.push(info[i]);
                }
            }
            let worker = new MenuElement("Персонал", -1, "/img/icons/worker.svg", $scope.assignmentInfoMan);
            let car = new MenuElement("Транспорт", -1, "/img/icons/car.svg", $scope.assignmentInfoCar);
            $scope.menu[0] = worker;
            $scope.menu[1] = car;
        }, params);
    };

    $scope.init = function () {
        $scope.addMainElem();
    };


    createWheel = function () {
        let colors = [];
        let numbers = [];
        for (let i = 0; i < $scope.currHero.rights.length; i++)
            if ($scope.currHero.rights[i].active === true && $scope.currHero.rights[i].amount > 0) {
                colors.push($scope.currHero.rights[i].color);
                numbers.push($scope.currHero.rights[i].amount);
            }
        let wheel = new SkillsWheel(numbers, colors, $scope.currHero.img, $scope.currHero.feature);
        let circle = wheel.createPic();
        return circle;
    };

    $scope.deleteMe = function (num) {
        $scope.marLen--;
        let marker = $scope.markers[num];
        marker.remove();
        let info = markersInfo[num];
        info.returnOne();
        $scope.$digest();
    };

    deleteMe = $scope.deleteMe;

    $scope.clearMap = function () {
        for (let i = 0; i < $scope.markers.length; i++)
            $scope.markers[i].remove();
        $scope.markers = [];
        map.off('click');
    };

    $scope.createObject = function () {
        map.off('click');
        map.on('click', function (e) {
            $scope.marLen++;
            let circle = createWheel();
            if ($scope.currElem !== undefined)
                $scope.currElem.decreaseAmount();
            if ($scope.currSquad !== undefined) {
                $scope.currSquad.decreaseAmount();
            }
            let isPos = $scope.currHero.decreaseAmount();
            if (isPos) {
                let coords = e.latlng;
                let marker = new L.Marker(coords, {icon: circle, draggable: true, opacity: 0.7});
                $scope.currHero.number = $scope.markers.length;
                marker.data = $scope.currHero;
                marker.number = $scope.markers.length;
                marker.bindPopup($scope.currHero.popup.popup_text);
                map.addLayer(marker);
                $scope.markers.push(marker);
                markersInfo.push($scope.currHero);
                if ($scope.$digest)
                    $scope.$digest();
            }
        })
    };

    $scope.destroyObject = function () {
        for (let i = 0; i < $scope.markers.length; i++)
            $scope.markers[i].remove();
        $scope.markers = [];
        markersInfo = [];
    };

    $scope.closeHero = function (num) {
        $scope.rightsSh = false;
        $scope.list[num].style = "";
        $scope.list[num].active = false;
        $scope.destroyObject();
    };

    $scope.closeAllHeroes = function () {
        for (let i = 0; i < $scope.list.length; i++) {
            $scope.closeHero(i);
        }
    };


    $scope.refresh = function () {

        $scope.destroyObject();
        for (let i = 0; i < $scope.menu.length; i++)
            if ($scope.menu[i])
                $scope.menu[i].returnAmount();
        $scope.shRefresh = false;
        $scope.marLen = 0;
    };


    $scope.loadPoResources = function () {
        let type = 'Staff';
        let params = "&typeStaff=Po&poId=" + terr[$scope.currFil].po[$scope.currPo].self.composite_id;
        //let params = "";
        objects.search(type, undefined, undefined, undefined, function (response) {
            let info = response.data.features;
            let countW = 0;
            let countC = 0;
            $scope.poInfoMan = [];
            $scope.poInfoCar = [];
            for (let i = 0; i < info.length; i++) {
                if (info[i].walk == true) {
                    countW += info[i].count;
                    $scope.poInfoMan.push(info[i]);
                } else {
                    countC += Number(info[i].count);
                    $scope.poInfoCar.push(info[i]);
                }
            }
            let worker = new MenuElement("Персонал", Math.round(countW), "/img/icons/worker.svg", $scope.poInfoMan);
            let car = new MenuElement("Транспорт", Math.round(countC), "/img/icons/car.svg", $scope.poInfoCar);
            $scope.menu[2] = worker;
            $scope.menu[3] = car;
            $scope.shPoResources = true;
        }, params);
    };

    $scope.loadResResources = function () {
        $scope.shResResources = true;
        let type = 'Staff';
        let params = "&typeStaff=Res&resId=" + $scope.currRes;
        objects.search(type, undefined, undefined, undefined, function (response) {
            let info = response.data.features;
            let countW = 0;
            let countC = 0;
            $scope.resInfoMan = [];
            $scope.resInfoCar = [];
            for (let i = 0; i < info.length; i++) {
                if (info[i].walk == true) {
                    countW += info[i].count;
                    $scope.resInfoMan.push(info[i]);
                }
                else {
                    countC += info[i].count;
                    $scope.resInfoCar.push(info[i]);
                }
            }
            let worker = new MenuElement("Персонал", Math.round(countW), "/img/icons/worker.svg", $scope.resInfoMan);
            let car = new MenuElement("Транспорт", Math.round(countC), "/img/icons/car.svg", $scope.resInfoCar);
            $scope.menu[4] = worker;
            $scope.menu[5] = car;
            $scope.shPoResources = true;
        }, params);
    };

    $scope.openArmy = function (arr, num) {
        $scope.squads = [];
        $scope.closeAll($scope.menu);
        $scope.closeSquads();
        $scope.currElem = $scope.menu[num];
        $scope.menu[num].style = "active";
        $scope.squads = $scope.menu[num].squads;
        $scope.shSquads = true;
    };

    $scope.closeAll = function (arr) {
        for (let i = 0; i < arr.length; i++)
            if (arr[i]) {
                arr[i].style = "";
                arr[i].active = false;
            }
        $scope.warnStyle = "";
    };

    $scope.openSquad = function (army) {
        map.off('click');
        $scope.closeHeroes();
        $scope.closeAll($scope.squads);
        $scope.currSquad = army;
        army.style = "active";
        $scope.heroes = $scope.currSquad.heroes;
        $scope.shHeroes = true;
        $scope.list = $scope.currSquad.heroes;
    };

    $scope.closeRights = function () {
        $scope.shRights = false;
        $scope.closeAll($scope.list);
    };

    $scope.closeRight = function (num) {
        $scope.list[num].style = "";
        $scope.list[num].active = false;
    };

    $scope.openRights = function (num) {
        $scope.rights = $scope.heroes[num].rights;
        $scope.rightsSh = true;
        $scope.closeRights();
        $scope.currHero = $scope.list[num];
        $scope.list[num].style = "active";
        $scope.list[num].active = true;
        $scope.createObject();
        $scope.shRights = true;
    };

    $scope.warnStyle = "";

    $scope.openWarning = function () {
        $scope.closeRights();
        $scope.closeAll($scope.menu);
        $scope.warnStyle = "active";
        $scope.currHero = $scope.warning;
        $scope.createObject();
        $scope.shRights = false;
    };

    $scope.closeArmies = function () {
        $scope.shArmies = false;
        $scope.closeSquads();
    };

    $scope.closeSquads = function () {
        $scope.shSquads = false;
        $scope.closeAll($scope.squads);
        $scope.closeHeroes();
    };

    $scope.closeHeroes = function () {
        $scope.shHeroes = false;
        $scope.closeAll($scope.heroes);
        $scope.closeRights();
    };

    //$scope.init();


});

myApp.directive('squads', function () {
    return {
        template: function () {
            return '<div class="trainingHeader"></div>' +
                '<a href="" ng-repeat="squad in squads" ng-click="openSquad(squad)" ng-click="choseSquad($index)" class="list-group-item list-group-item-action flex-column align-items-start {{squad.style}}">' +
                '            <div>\n' +
                '<img width="32" src="{{squad.img}}">' +
                '              <span class="mb-1 menuItemText">{{squad.name}}</span>\n' +
                '            <span class="mb-1 badge badge-secondary" ng-bind-html="squad.amount"></span>\n' +
                '            </div>\n' +
                '</a>'
        }
    }
});

myApp.directive('heroes', function () {
    return {
        template: function () {
            return '<div class="trainingHeader"></div>' +
                '<a href="" ng-repeat="resource in heroes" ng-click="openRights($index)" class="list-group-item list-group-item-action flex-column align-items-start {{resource.style}}">' +
                '            <div>\n' +
                '<img width="32" src="{{resource.img}}">' +
                '              <span class="mb-1  menuItemText">{{resource.name}}</span>\n' +
                '            <span class="mb-1 badge badge-secondary" ng-bind-html="resource.amount"></span>\n' +
                '            </div>\n' +
                '</a>'
        }
    }
});

myApp.directive('rights', function () {
    return {
        template: function () {
            return '<div>\n' +
                '<div class="trainingHeader">{{currHero.type}}</div>' +
                '  <div class="card-body padding-0">\n' +
                '<ul class="list-group"> <li class="list-group-item" ng-repeat="right in currHero.rights">' +
                '<input class="form-check-label pr-0 ml-0" ng-model="right.active" type="checkbox" ng-disabled="right.dis">' +
                '<span class="badge badge-secondary badge-pill" ng-bind-html="right.amount"></span> ' +
                '<span class="menuItemText" style="{{right.style}}">{{right.name}}</span> ' +
                '</li> </ul>' +
                '  </div>\n' +
                //  '<a href="" class="btn btn-secondary width-100" ng-click="refresh()" >Обновить</a>' +
                '' +
                '<img width="32" class="card-img-bottom" src="{{currHero.img}}" alt="Card image cap"/>' +
                '</div>'
        }
    }
});

myApp.directive('menu', function () {
    return {
        template: function () {
            return '<div class="list-group col-3 pr-0 mr-0">\n' +
                '<div class="trainingHeader" ng-show="shResResources">Ресурсы&nbsp;РЭС</div>\n' +
                '<a href="" ng-show="shResResources" ng-click="openArmy(resInfoMan, 4)" class="list-group-item list-group-item-action flex-column align-items-start {{menu[4].style}}">' +
                '            <div>\n' +
                '<img src="{{menu[4].img}}">' +
                '              <span class="mb-1 menuItemText">{{menu[4].name}}</span>\n' +
                '            <span class="mb-1 badge badge-secondary">{{menu[4].amount}}</span>\n' +
                '            </div>\n' +

                '</a>\n' +
                '<a href="" ng-show="shResResources" ng-click="openArmy(resInfoCar, 5)" class="list-group-item list-group-item-action flex-column align-items-start {{menu[5].style}}">' +
                '            <div>\n' +
                '<img src="{{menu[5].img}}">' +
                '              <span class="mb-1 menuItemText">{{menu[5].name}}</span>\n' +
                '            <span class="mb-1 badge badge-secondary">{{menu[5].amount}}</span>\n' +
                '            </div>\n' +

                '</a>\n' +
                '<div class="trainingHeader" ng-show="shPoResources">Ресурсы&nbsp;ПО</div>\n' +
                '<a href="" ng-show="shPoResources" ng-click="openArmy(poInfoMan, 2)" class="list-group-item list-group-item-action flex-column align-items-start {{menu[2].style}}">' +
                '            <div>\n' +
                '<img src="{{menu[2].img}}">' +
                '              <span class="mb-1 menuItemText">{{menu[2].name}}</span>\n' +
                '            <span class="mb-1 badge badge-secondary">{{menu[2].amount}}</span>\n' +
                '            </div>\n' +

                '</a>\n' +
                '<a href="" ng-show="shPoResources" ng-click="openArmy(poInfoCar, 3)" class="list-group-item list-group-item-action flex-column align-items-start {{menu[3].style}}">' +
                '            <div>\n' +
                '<img src="{{menu[3].img}}">' +
                '              <span class="mb-1 menuItemText">{{menu[3].name}}</span>\n' +
                '            <span class="mb-1 badge badge-secondary">{{menu[3].amount}}</span>\n' +
                '            </div>\n' +
                '</a>\n' +
                '<div class="trainingHeader">Командированные</div>\n' +
                '<a href="" ng-click="openArmy(assignmentInfoMan, 0)" class="list-group-item list-group-item-action flex-column align-items-start {{menu[0].style}}">' +
                '            <div>\n' +
                '<img src="{{menu[0].img}}">' +
                '              <span class="mb-1 menuItemText" >{{menu[0].name}}</span>\n' +
                '            <span class="mb-1 badge badge-secondary" ng-bind-html="menu[0].amount"></span>\n' +
                '            </div>\n' +
                '</a>\n' +
                '<a href="" ng-click="openArmy(assignmentInfoCar, 1)" class="list-group-item list-group-item-action flex-column align-items-start {{menu[1].style}}">' +
                '            <div>\n' +
                '<img src="{{menu[1].img}}">' +
                '              <span class="mb-1 menuItemText">{{menu[1].name}}</span>\n' +
                '            <span class="mb-1 badge badge-secondary" ng-bind-html="menu[1].amount"></span>\n' +
                '            </div>\n' +
                '</a>\n' +
                '<div class="trainingHeader">Авария</div>\n' +
                '<a href="" ng-click="openWarning()" class="list-group-item list-group-item-action flex-column align-items-start {{warnStyle}}">' +
                '            <div>\n' +
                '<img src="/img/icons/lighting.svg">' +
                '            </div>\n' +
                '</a>\n' +

                '<div ng-show="shRefresh" class="list-group-item list-group-item-action flex-column align-items-start">' +
                '<input type="button" class="btn btn-secondary w-100" value="Очистить" ng-click="refresh()">' +
                '</div>\n' +
                '</div>\n' +
                '</div>' +
                '<div class="list-group col-3 mx-0 px-0" ng-show="shSquads">' +
                '<squads></squads>' +
                '</div>' +
                '<div class="list-group col-3 mx-0 px-0" ng-show="shHeroes">' +
                '<heroes></heroes>' +
                '</div>' +
                '<div class="list-group col-3 mx-0 px-0" ng-show="shRights">' +
                '<rights></rights>' +
                '</div>'
        }
    }
});

myApp.directive('res', function () {
    return {
        template: function () {
            return '<div class="form-group row my-1">\n' +
                '  <div for="PO" class="col-sm-3">Филиал</div>\n' +
                '  <div class="col-sm-9">\n' +
                '      <select class="menuItemText tr_select" id="PO" ng-model="currFil">\n' +
                '        <option ng-repeat = "fil in filials" value = "{{fil.name}}">{{fil.name}}</option>\n' +
                '      </select>\n' +
                '  </div>\n' +
                '</div>\n' +
                '<div class="form-group row my-1" ng-show="shPo">\n' +
                '  <div for="RES" class="col-sm-3">ПО</div>\n' +
                '  <div class="col-sm-9">\n' +
                '      <select class="menuItemText tr_select" id="RES" ng-model="currPo">\n' +
                '        <option ng-repeat = "po in pos" value="{{po.name}}">{{po.name}}</option>\n' +
                '      </select>\n' +
                '  </div>\n' +
                '</div>\n' +
                '<div class="form-group row my-1" ng-show="shRes">\n' +
                '  <div for="RES1" class="col-sm-3">РЭС</div>\n' +
                '  <div class="col-sm-9 mb-2">\n' +
                '      <select class="menuItemText tr_select" id="RES1" ng-model="currRes">\n' +
                '        <option ng-repeat="res in ress" value="{{res.id}}">{{res.name}}</option>\n' +
                '      </select>\n' +
                '  </div>\n' +
                '</div>'
        }
    }
});