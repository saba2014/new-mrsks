/* global L */

L.Control.kingdomControl = L.Control.Distance.extend({

    setAction(func){
      this.func = func;
    },

    onAdd: function (map) {
        var className = 'leaflet-control-distance leaflet-control-short-path',
            container = this._container = L.DomUtil.create('div', className);
        this.options.position = "topleft";

        this._link = this._createButton('Тренировка',
            'leaflet-control-distance leaflet-control-kingdom', container, this.callback, this);
        this.markers = [];
        this.line = {};
        this.line_holders = [];
        this.active_holder = undefined;
        L.DomUtil.addClass(this._link, 'leaflet-control-distance-active');
    this._active = true;
        return container;
    },

    onRemove: function (map) {
        this._active = true;
        /*if (!this._active) {
            this._calc_disable();
        }*/
    },

    callback: function () {
        if (this._active) {
            //this._calc_disable();
            L.DomUtil.removeClass(this._link, 'leaflet-control-distance-active');
        } else {
            //this._calc_enable();
            L.DomUtil.addClass(this._link, 'leaflet-control-distance-active');
        }
        this._active=!this._active;
        this.func();
    },

});
