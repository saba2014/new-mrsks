myApp.controller('TabCtrl', function ($scope, objects,$uibModal,$document,$interval) {
    
            $scope.ForClose=false;
        $scope.ForCloseClass="nav-item dropdown";

        $interval(function(){
            angular.element('#last').trigger('click');
        },2000,1);
        
    $scope.tab = 0;
    $scope.messages = [];
    $scope.admin = true;
    $scope.itemPerPage = 10;
    var LimitTextSize = 10;
    
    $scope.selectTab = function (setTab) {
        $scope.tab = setTab;
    };

    $scope.isSelected = function (checkTab) {
        return $scope.tab === checkTab;
    };
    
    var Chosen={
        'border-top': '1px solid lightgray',
        'border-left': '1px solid lightgray',
        'border-right': '1px solid lightgray',
        'border-bottom': '0'
    }
    
    var NotChosen={
        'border-top': '0',
        'border-left': '0',
        'border-right': '0',
        'border-bottom': '1px solid lightgray'
    }
    
    $scope.CurrStyle = function(num){
        if (num == $scope.tab) return Chosen;
        return '';//NotChosen;
    }
    
    objects.search('Message',undefined,undefined,undefined,function(data){
        $scope.messages = data.data.features;
    },undefined);

    $scope.status = {
        isopen: true
    };
    

    $scope.update=function(param,coll,type,field){
        objects.update(param._id.$oid,"_id",coll,type,field,function(data){
        });
    };
    
    $scope.LimitText=function(text){
        return GetFirstSymbols(LimitTextSize,text);
    }

    $scope.open = function (size,data,parentSelector) {
        var admin = $scope.admin;
        var modalInstance = $uibModal.open({
            animation: $scope.animationsEnabled,
            ariaLabelledBy: 'modal-title',
            ariaDescribedBy: 'modal-body',
            templateUrl: 'MessageContent',
            controller: 'MessageCtrl',
            controllerAs: '$ctrl',
            size: size,
            resolve: {
                data: function(){
                    return data;
                },
                admin: function(){
                    return admin;
                }
            }
        });
        
        modalInstance.result.then(function () {
          
        }, function () {
          
        });
    };
    
     
});

