class Po extends Layer {
    constructor(map, Leaflet, id, parent) {
        super(map, Leaflet, id);
        this._type = 'Po';
        this._search = false;
        this._id = id;
        this._zoom = poScale;
        this._maxZoom = resScale;
        this.near = 1;
        this.arguments = '&composite_id=' + this._id;
        this.parent = parent;
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
        if(this.parent){
            this.parent.notifyParent(true);
        }
    }

    onLayerRemove() {
        this.map.removeLayer(this.LayerGJSON);
        this.onMap--;
        if(this.parent){
            this.parent.notifyParent(true);
        }
    }

}