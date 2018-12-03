angular.module('myApp').controller('universeWaysInstance', function ($scope, $uibModalInstance, obj,
                                                                     type, objects, FileUploader) {
    let $ctrl = this;
    $ctrl.properties = obj.properties;
    $ctrl.geometry = obj.geometry;
    $ctrl.newTplnr = "";

    $ctrl.deleteTplnr=function(num){
      $ctrl.properties.tplnrs.splice(num,1);
    };

    $ctrl.addTplnr=function(){
        $ctrl.properties.tplnrs.splice($ctrl.properties.tplnrs.length,0,$ctrl.newTplnr);
        $ctrl.newTplnr="";
    };

    /*$ctrl.$watch('tplnr', function(newVal, oldVal) {
      //  console.log(newVal);
      //  console.log(oldVal);
    });*/

    $ctrl.focus=function(num,value){
        $ctrl.properties.tplnrs[num]=value;
    };

        $ctrl.ok = function () {
        objects.update(obj._id.$oid, '_id', "UniverseWays", "name", $ctrl.properties.name);
        objects.update(obj._id.$oid, '_id', "UniverseWays", "tplnrs", $ctrl.properties.tplnrs);
        $uibModalInstance.close();
    };
});