//var MapApp = angular.module('MapApp', ['ngAnimate', 'ngSanitize', 'ui.bootstrap']);

var OpenMessageMenu;
var OpenLayersMenu;
var SwitchOffTrack;
var CurrentMarker;

window.myApp.controller('MapCtrl', function ($scope, $uibModal, $document, objects, $log) {

    $scope.LayersMenuSh = false;
    $scope.admin = false;
    $scope.kingdomSh = false;
    $scope.kingdomSm = false;
    $scope.kingdomClass="animated bounceInLeft";
    $scope.closeKingdomStyle="animated bounceInLeft";

    JWT.getRole(function(){
       if (JWT.usersRole>2)
           $scope.admin = true;;
    });
    if (adminenabled === 1) $scope.admin = true;

    OpenMessageMenu = function (data) {
        $scope.open('lg', data);
    };

    OpenLayersMenu = function () {
        $scope.LayersMenuSh = true;
        $scope.$digest();
    };

    SwitchOffTrack = function () {
        $scope.LayersMenuSh = !$scope.LayersMenuSh;
    };
    SwitchOffTrackButton = function () {
        $scope.LayersMenuSh = false;
        $scope.$digest();
    };

    $scope.DeleteLayers = function () {
        DeleteLayersInWork();
    };

    $scope.update = function (param, coll, type, field) {
        objects.update(param._id.$oid, "_id", coll, type, field, function (data) {
        });
        cont.Check();
    };


    $scope.open = function (size, data, parentSelector) {
        function get_info(item) {
            var text = "";
            if (item.properties.info !== undefined) {
                var lines = item.properties.info.split(/\r\n|\r|\n/g);
                for (var i = 0; i < lines.length; i++) {
                    text += '<br>' + lines[i];
                }
            }
            return text;
        }

        var admin = $scope.admin;
        get_info(data);
        data.properties.from = "IMEI: " + data.properties.deviceId;
        if (data.properties.worker_name !== undefined) {
            data.properties.from = data.properties.worker_name + " " + data.properties.from;
        }
        var modalInstance = $uibModal.open({
            animation: $scope.animationsEnabled,
            ariaLabelledBy: 'modal-title',
            ariaDescribedBy: 'modal-body',
            templateUrl: 'MessageContent',
            controller: 'MessageCtrl',
            controllerAs: '$ctrl',
            size: size,
            resolve: {
                data: function () {
                    return data;
                },
                admin: function () {
                    return admin;
                }
            }
        });

        modalInstance.result.then(function () {

        }, function () {

        });
    };

    $scope.$watch('LayersMenuSh', function () {
        if ($scope.LayersMenuSh === true) {
            document.getElementsByClassName('LayersMenuStyle')[0].classList.add('animated');
        }
        else {
            document.getElementsByClassName('LayersMenuStyle')[0].classList.remove('animated');
        }
    });

    $scope.kingdom = function(){
        if($scope.kingdomSh) {
            $scope.kingdomClass="animated bounceOutLeft";
            $scope.$broadcast('closeKingdom',null);
            map.removeControl(trainingControl);
        } else {
            $scope.kingdomClass="animated bounceInLeft";
            $scope.$broadcast('initKingdom',null);
            trainingControl.setAction($scope.hideKingdom);
            map.addControl(trainingControl);
        }
        $scope.kingdomSh = !$scope.kingdomSh;
        $scope.kingdomSm = false;
        // $scope.$digest();
    };

    $scope.hideKingdom=function(){
        if(!$scope.kingdomSm) {
            $scope.kingdomClass="animated bounceOutLeft";
            $scope.closeKingdomStyle="animated bounceInLeft";
        } else {
            $scope.kingdomClass="animated bounceInLeft";
            $scope.closeKingdomStyle="animated bounceOutLeft";
        }
        $scope.kingdomSm = !$scope.kingdomSm;
        if ($scope.$digest)
            $scope.$digest();
    };

    let event = new CustomEvent('showItem', {detail: 'btnTrackContainer'});
    window.dispatchEvent(event);
});

