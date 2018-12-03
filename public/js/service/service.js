window.myApp.factory('objects', function ($http) {
    return {
        createLogWindow: function(){
           let _JWT = new JWTAccessManager();
           _JWT.createLoginWindow();
        },
        refreshToken: function(){
            let token = new TokenStorage();
            token.checkRelevance();
        },
        severalSimpleRequests: function(urlsArr, callback){
            for (let i=0;i<urlsArr.length;i++){
                $http.get(urlsArr[i]).then(callback, this.createLogWindow);
            }
            this.refreshToken();
        },
        severalRequests: function(type, query, skip, fields, callback, parametrs){
            if (query===undefined || query==="") {
                this.search(type, undefined, skip, undefined, callback, parametrs);
                return;
            }
            for (let i =0;i<fields.length;i++){
                let request = 'api/getobjs?type=' + type;
                if (query!==undefined){
                    let requestFul = request+'&'+fields[i]+'='+query;
                    let requestReg = request+ '&fieldRegex=properties.' + fields[i] + '&regex=' + query;
                    $http.get(requestFul).then(callback, this.createLogWindow);
                    $http.get(requestReg).then(callback, this.createLogWindow);
                }
            }
            this.refreshToken();
        },
        simpleRequest: function(url,callback){
            $http.get(url).then(callback, this.createLogWindow);
            this.refreshToken();
        },
        search: function (type, query, skip, filde_regex, callback, parametrs, errorCallback) {
            let self=this;
            var request = 'api/getobjs?type=' + type;
            if (query !== undefined) {
                request += '&fieldRegex=' + filde_regex + '&regex=' + query;
            }
            if (skip !== undefined) {
                request += '&skip=' + (skip);
            }
            if (parametrs !== undefined) {
                request += parametrs;
            }
            $http.get(request).then(callback, function(){
                self.createLogWindow();
                if (errorCallback)
                    errorCallback();
            });
            this.refreshToken();
        },
        remove: function (type, query, dell_opory, callback, errorCallback) {
            let self = this;
            $http({
                method: 'POST',
                url: 'api/deleteObject',
                tplnr: query,
                type: type,
                dell_opory: dell_opory,
                data: "tplnr=" + query + "&type=" + type + "&dell_opory=" + dell_opory,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            }).then(callback, function(){
                self.createLogWindow();
                if (errorCallback)
                    errorCallback();
            });
            this.refreshToken();
        },
        remove_obj: function (type, query, query_field, callback, errorCallback) {
            let self = this;
            $http({
                method: 'POST',
                url: 'api/deleteObject',
                tplnr: query,
                type: type,
                query_field: query_field,
                data: query_field + "=" + query + "&type=" + type,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            }).then(callback, function(){
                self.createLogWindow();
                if (errorCallback)
                    errorCallback();
            });
            this.refreshToken();
        },
        delete_obj: function (type, field, idfield, callback,errorCallback) {
            let self = this;
            $http({
                method: 'POST',
                url: 'api/del',
                idfield: idfield,
                type: type,
                field: field,
                data: "type=" + type + "&idfield=" + idfield + "&field=" + field,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            }).then(callback, function(){
                self.createLogWindow();
                if (errorCallback)
                    errorCallback();
            });
            this.refreshToken();
        },
        update_file: function (id, idfield, href, type, type_href, name, href_id, callback, uploader, self) {
            if ((uploader !== null) && (uploader !== undefined) && (uploader.queue.length > 0)) {
                uploader.uploadAll();
                uploader.onSuccessItem = function (fileItem, response, status, headers) {
                    if (response.answer === "Successful") {
                        self.update_href(id, idfield, response.url, type, type_href, name, href_id, callback);
                    }
                };
            } else {
                self.update_href(id, idfield, href, type, type_href, name, href_id, callback);
            }
            this.refreshToken();
        },
        update_href: function (id, idfield, href, type, type_href, name, href_id, callback, errorCallback) {
            var arg = "";
            if ((href_id !== null) && (href_id !== undefined)) {
                arg = "&href_id=" + href_id;
            }
            let self = this;
            $http({
                method: 'POST',
                url: 'api/update',
                data: "idfield=" + idfield + "&id=" + id + "&type=" + type + "&href=" + encodeURIComponent(href) +
                        "&type_href=" + type_href + "&name=" + encodeURIComponent(name) + arg,
                name: name,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            }).then(callback, function(){
                self.createLogWindow;
                if (errorCallback)
                    errorCallback();
            });
            this.refreshToken();
        },
        svgToPng: function(file, callback){
            $http({
                method: 'POST',
                url: 'api/svgToPng?svg='+file,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            }).then(callback, function(){
                if (errorCallback)
                    errorCallback();
            });
            this.refreshToken();

        },
        update: function (id, idfield, type, typefield, field, callback, errorCallback) {
            field = angular.toJson(field);
            let self = this;
            $http({
                method: 'POST',
                url: 'api/update',
                id: id,
                type: type,
                typefield: typefield,
                field: field,
                idfield: idfield,
                data: "idfield=" + idfield + "&id=" + id + "&type=" + type + "&typefield=" + typefield +
                        "&field=" + field,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            }).then(callback, function(){
                self.createLogWindow;
                if (errorCallback)
                    errorCallback();
            });
            this.refreshToken();
        },
        delete_href: function (id, idfield, href_id, type, callback, errorCallback) {
            let self = this;
            $http({
                method: 'POST',
                url: 'api/deletehref',
                id: id,
                type: type,
                href_id: href_id,
                idfield: idfield,
                data: "idfield=" + idfield + "&id=" + id + "&type=" + type + "&href_id=" + href_id,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            }).then(callback, function(){
                self.createLogWindow;
                if (errorCallback)
                    errorCallback();
            });
            this.refreshToken();
        },
        
        send_message: function (message, callback, errorCallback) {
            let self = this;
            $http({
                method: 'POST',
                url: 'api/message',
                message: message,
                all: 1,
                data: "message=" + message + "&all=" + 1,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            }).then(callback, function(){
                self.createLogWindow();
                if (errorCallback)
                    errorCallback();
            });
            this.refreshToken();
        },
        
        add: function(name,lat,lon,group,display,info,callback, errorCallback){
            let self = this;
            $http({
                method: 'POST',
                url: 'api/create',
                name: 'test',
                data:  'name='+name+"&lat="+lat+'&lon='+lon+'&group='+group+'&display='+display+'&info='+info,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            }).then(callback, function(){
                self.createLogWindow;
                if (errorCallback)
                    errorCallback();
            });
            this.refreshToken();
        }
    };
});
