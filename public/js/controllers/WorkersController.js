
let onCounter = 0;

window.myApp.controller("WorkersController", ['$http', '$scope', function ($http, $scope) {
    $scope.people = [];
    $scope.first_date;
    $scope.last_date;
    $scope.input = '';
    $scope.style = 'workers-turned';
    $scope.imgSrc = 'css/images/001-chevron.svg';

    $scope.add = function () {
        if ($scope.people.indexOf($scope.input) === -1) {
            $scope.people.push($scope.input);
            $scope.input = '';
            $scope.update_names();
        }
        if($scope.people.length > 0) {
           angular.element('#reset-block').addClass('show-reset');

        }

    };

    $scope.clear = function () {
        $scope.people = [];
        $scope.update_names();
        angular.element('#reset-block').removeClass('show-reset');
    };
    $scope.delete = function (item) {
        let index = $scope.people.indexOf(item);
        $scope.people.splice(item, 1);
        $scope.update_layers();
    };
    $scope.update_layers = function () {
        cont.Check();
    };
    $scope.set_date = function () {
        let date = new Date();
        $scope.last_date = new Date();
        date.setMonth(date.getMonth() - 1);
        $scope.first_date = new Date(date);
        update_first_date();
        update_last_date();
    };
    function update_first_date() {
        for (let i = 0; i < cont.workers_tracks.length; i++) {
            cont.workers_tracks[i].time_a = $scope.first_date ? $scope.first_date.toISOString().split('T')[0] : '0000-00-00';
            cont.workers_counters[i].time_a = $scope.first_date ? $scope.first_date.toISOString().split('T')[0] : '0000-00-00';
        }
        $scope.update_layers();        let currDate = new Date();
        currDate.setMonth(currDate.getMonth() - 1);
    }

    function update_last_date() {
        for (let i = 0; i < cont.workers_tracks.length; i++) {
            cont.workers_tracks[i].time_b = $scope.last_date ? $scope.last_date.toISOString().split('T')[0] : '0000-00-00';
            cont.workers_counters[i].time_b = $scope.last_date ? $scope.last_date.toISOString().split('T')[0] : '0000-00-00';
        }
        $scope.update_layers();
    }

    $scope.update_names = function () {
        for (let i = 0; i < cont.workers_tracks.length; i++) {
            cont.workers_tracks[i].people = $scope.people;
            cont.workers_mobile[i].people = $scope.people;
            cont.workers_counters[i].people = $scope.people;
        }
        $scope.update_layers();
    };

    $scope.setStyle = function () {
        if ($scope.style === 'workers-turned') {
            $scope.style = 'workers-deployed';
            $scope.imgSrc = 'css/images/002-chevron.svg';

        }
        else {
            $scope.style = 'workers-turned';
            $scope.imgSrc = 'css/images/001-chevron.svg';
               }
    };

    $scope.getUsersByName = function (value) {
       return $http({
            method: 'GET',
            url: window.location.origin + '/api/getNames&name=' + value
        }).then(function (response) {
            return response.data;
        }, function (response) {
            console.error("Error occured trying to load people names");
        });
    };

    $scope.$watch('first_date',  update_first_date);
    $scope.$watch('last_date', update_last_date);
    $scope.set_date();
}]);

window.myApp.directive('outputpeople', function () {
    return {
        template: function () {
            return '<span class="output-people"> {{man}}</span>'
        }
    }
});

window.myApp.directive('ngimg', function () {
    return {
        template: function () {
            return '<img class="h-image leaflet-control-distance" ng-src="{{imgSrc}}" alt="открыть">'
        }
    }
});


