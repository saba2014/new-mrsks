/*
 * Класс сообщение, наследник класса Layer
 */

class Messages extends Layer {
    constructor(map, Leaflet) {
        super(map, Leaflet);
        this._type = 'Message';
        this.Popup = new Popup_message();
        this.type_image = "message";
        this.image_height = 20;
        this.image_width = 20;
    }

    pointToLayer(feature, latlng,data) {
        if (feature.properties.vis==false) {
            let myIcon = L.icon({
                iconUrl: 'img/icons/warning.svg',
                iconSize: [30, 30]
            });
            let marker = new L.marker(latlng, {icon: myIcon});
            marker.on('click',function(){
                CurrentMarker = this;
            });
  
            return marker;
        }
    }

    onEachFeatureLayerGJSON(item, layer, data) {
        if (item)
            if (item.properties.vis==false)
        {
            data.popup.tooltip_text = item;
            data.popup.tooltip_text_more = item;
            data.popup.bind_tooltip(layer, data.TooltipFunc);
        }
    }
    
}
