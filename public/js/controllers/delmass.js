window.myApp.controller('DeleteCtrl', function ($scope, $http, $timeout) {
    $scope.type = "Opory";
    $scope.button = "Удалить";
    $scope.dell_opory = false;

    $scope.remove = function (data) {
        console.log(data);
        $http({
            method: 'POST',
            url: 'api/deleteObject',
            tplnr: data,
            type: $scope.type,
            dell_opory: $scope.dell_opory,
            data: "tplnr=" + data + "&type=" + $scope.type + "&dell_opory=" + $scope.dell_opory,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        }).then(function (data) {
            console.log(data);
            $scope.ret = data.data.deleted;
            $scope.button = "Удалено " + data.data.deleted + " объектов";
            $scope.data = [];
        });
    };

    $scope.onChange = function () {
        $scope.button = "Удалить";
    };
});
