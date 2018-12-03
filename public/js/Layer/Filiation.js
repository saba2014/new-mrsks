class Filiation extends Layer {
    constructor(map, Leaflet, id) {
        super(map, Leaflet, id);
        this._type = 'Filiation';
        this._search = false;
        this._id = id;
        this._zoom = filiationScale;
        this._maxZoom = poScale;
        this.near = 1;
        this.arguments = '&id=' + this._id;
        this.popup = new Popup_res();
        this.onMap=0;
    }

    style(feature) {
        var ss = {
            color: feature.properties.color
        };
        return ss;
    }

    is_has() {
        return this.map.hasLayer(this.LayerGJSON);
    }

    notifyParent(value){
        if(value){
            this.onLayerAdd();
        }
        else{
            this.onLayerRemove();
        }
    }

    onLayerAdd() {
        this.map.removeLayer(this.LayerGJSON);
        this.onMap++;
        if ((this.map.getZoom() > this._zoom)) {
            this._add();
        }
    }

    onLayerRemove() {
        this.map.removeLayer(this.LayerGJSON);
        this.onMap--;
    }
}