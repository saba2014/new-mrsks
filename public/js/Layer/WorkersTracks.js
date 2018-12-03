class WorkersTracks extends Layer {
    constructor(map, Leaflet) {
        super(map, Leaflet);
        this._type = 'MobileControllersTracks';
        this._zoom = 9;
        this.people = [];
        this.region = [];
        let currDate = new Date();
        currDate.setMonth(currDate.getMonth() - 1);
        this.time_a = new Date(currDate).toISOString().split('T')[0];
        this.time_b = new Date().toISOString().split('T')[0];
        this.onMap=0;
    }

    style(feature, layer) {
        return {};
    }

    decorateLines(layer, feature) {
        let decorator = L.polylineDecorator(layer, {
            patterns: [
                {
                    offset: '3%',
                    repeat: '50',
                    symbol: L.Symbol.arrowHead({
                        pixelSize: 10,
                        polygon: false,
                        pathOptions: {color: '#000', stroke: true}
                    })
                }
            ]
        });
        this.LayerGJSON.addLayer(decorator);
    }

    onEachFeatureLayerGJSON(feature, layer, data) {
        data.decorateLines(layer, feature);
        if (feature.properties.color) {
            layer.setStyle({color: feature.properties.color});
        }
        let date = new Date(feature.properties.day * 1000);
        let date_text = date.toLocaleString().split(',')[0];
        let text = '<b>Владелец: </b>' + feature.properties.owner + '<br>' +
            '<b>Ableser: </b>' + feature.properties.Ableser + '<br>' +
            '<b>Дата: </b>' + date_text;
        layer.bindTooltip(text, {sticky: true});
    }


    onLayerAdd(region) {
        this.region.push(region);
        document.getElementById('p_Workers').style.display = 'block';
        onCounter++;
        this.map.removeLayer(this.LayerGJSON);
        this.onMap++;
        if ((this.map.getZoom() > this._zoom)) {
            this._add();
        }
    }

    onLayerRemove(region) {
        let index= this.region.indexOf(region);
        this.region.splice(index,1);
            this.map.removeLayer(this.LayerGJSON);
            this.onMap--;
        onCounter--;
        if (onCounter===0) {
            document.getElementById('p_Workers').style.display = 'none';
            if(document.getElementById("p_Workers").classList.contains('workers-deployed')){
                document.getElementById('turnFilter').click();
            }
        }
    }

    actualiseLayerGJSON() {
        if (this.map.hasLayer(this.LayerGJSON)) {
            this.LayerGJSON.unbindTooltip();
            this._cluster_check();
            if (this.back) {
                this.Back();
            }
        }
    }

    _get_api_url() {
        let people = JSON.stringify(this.people);
        let url = 'api/getobjs?type=' + this._type;
        if(this.time_a!=='0000-00-00'){
            url +='&timeA=' + this.time_a;
        }
        if(this.time_b!=='0000-00-00'){
            url +='&timeB=' + this.time_b;
        }
        url += '&lon1=' + this.map.getBounds().getWest() + '&lon2=' + this.map.getBounds().getEast() + '&lat1=' +
            this.map.getBounds().getSouth() + '&lat2=' + this.map.getBounds().getNorth();
        url += '&bukrs=["' + this.region.join('","') + '"]';
        if (people !== "[]") {
            url += '&names=' + people;
        }
            return url;
    }

    check_popup(popup, near) {
    }

    pointToLayer(){
    }

}