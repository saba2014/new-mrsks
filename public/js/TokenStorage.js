class TokenStorage {
    constructor(access = null, refresh = null, expire = null, before = 5) {
        if (access != null) {
            this.accessT = access;
            this.refreshT = refresh;
            this.expireT = expire;

            // долой сохранение токенов отсюда
            this.saveTokens();
        }
        else {
            // А если я захочу создать просто пустой экземпляр класса? Я всё-равно получу то, что в storage...??
            this.accessT = localStorage.getItem('access_token');
            this.refreshT = localStorage.getItem('refresh_token');
            this.expireT = localStorage.getItem('expire');
        }
        this.before = before;
    }

    saveInStorage() {
        localStorage.setItem('access_token', this.accessT);
        localStorage.setItem('refresh_token', this.refreshT);
        localStorage.setItem('expire', this.expireT);
    }

    saveInCookies() {
        let acc = "access_token=" + this.accessT + "; expires=" + this.expireT;
        let ref = "refresh_token=" + this.refreshT + "; expires=" + this.expireT;
        let expire = "expire=" + this.expireT + "; expires=" + this.expireT;
        document.cookie = acc;
        document.cookie = ref;
        document.cookie = expire;
    }

   /* refreshTokens2(callback){
        let self = this;
        this.makeRefreshRequest(function (data) {
            if (data != false) {
                let obj = data;
                self.refreshT = obj['refresh_token'];
                self.accessT = obj['access_token'];
                self.expireT = obj['expire'];
                self.saveInStorage();
                self.saveInCookies();
                callback(true);
            }
            else {
                callback(false);
            }
        })
    }*/

    refreshTokens() {
        let self = this;
        this.makeRefreshRequest(function (data) {
            if (data != false) {
                let obj = data;
                self.refreshT = obj['refresh_token'];
                self.accessT = obj['access_token'];
                self.expireT = obj['expire'];
                self.saveInStorage();
                self.saveInCookies();
            }
            else {

            }
        })
    }

    // на данном этапе обязательно должны существовать cookies,
    // поскольку происходит проверка наличия в них refresh token
    makeRefreshRequest(callback) {
        let refresh = this.refreshT;
        $.ajax({
            url: 'api/refreshTokens',
            beforeSend: function (request) {
            },
            type: 'GET',
            success: function (data) {
                callback(data);
            },
            error: function (err) {
                callback(false);
            }
        });
    }

    getRefreshToken() {
        return this.refreshT;
    }

    getAccessToken() {
        return this.accessT;
    }

   /* checkRelevance2(callback){
        let self = this;
        if (self.expireT) {
            if ((timeNow() + self.before) > self.expireT) {
                self.refreshTokens2(callback);
            }
            else{
                callback(true);
            }
        }
    }*/

    checkRelevance() {
        let self = this;
        if (self.expireT)
            if ((timeNow() + self.before) > self.expireT) {
                self.refreshTokens();
            }
    }

    deleteFromStorage() {
        localStorage.removeItem('access_token');
        localStorage.removeItem('refresh_token');
        localStorage.removeItem('expire');
    }

    deleteFromCookies() {
        document.cookie = 'access_token=;expires=Thu, 01 Jan 1970 00:00:01 GMT;';
        document.cookie = 'refresh_token=;expires=Thu, 01 Jan 1970 00:00:01 GMT;';
        document.cookie = 'expire=;expires=Thu, 01 Jan 1970 00:00:01 GMT;'
    }

    deleteTokens() {
        this.deleteFromCookies();
        this.deleteFromStorage();
    }

    saveTokens() {
        this.saveInCookies();
        this.saveInStorage();
    }
}


