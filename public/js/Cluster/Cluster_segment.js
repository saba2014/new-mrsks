class Cluster_segment extends Cluster {
    constructor() {
        super();
    }

    get_cluster(L, map, colors) {
        let temp_icon_cluster = L.Icon.extend({
            options: {
                iconSize: new L.Point(44, 44)
            },
            createIcon: function () {
                // based on L.Icon.Canvas from shramov/leaflet-plugins (BSD licence)
                let e = document.createElement('canvas');
                e.className = 'canvase_cluster';
                let s = this.options.iconSize;
                e.width = s.x;
                e.height = s.y;
                this.draw(e.getContext('2d'), s.x, s.y);
                return e;
            },
            draw: function (canvas, width, height) {

                let start = 0;
                let count = this.options.marker.length;
                let stats = [];
                let color_count = colors.length;
                for (let i = 0; i < color_count; ++i) {
                    stats[i] = 0;
                }
                for (let i = 0; i < count; ++i) {
                    stats[this.options.marker[i].category]++;
                }
                for (let i = 0; i < color_count; ++i) {
                    let size = stats[i] / count;


                    if (size > 0) {
                        canvas.beginPath();
                        canvas.moveTo(22, 22);
                        canvas.fillStyle = colors[i];
                        let from = start + 0.14,
                            to = start + size * Math.PI * 2;

                        if (to < from) {
                            from = start;
                        }
                        canvas.arc(22, 22, 22, from, to);

                        start = start + size * Math.PI * 2;
                        canvas.lineTo(22, 22);
                        canvas.fill();
                        canvas.closePath();
                    }

                }

                canvas.beginPath();
                canvas.fillStyle = 'white';
                canvas.arc(22, 22, 18, 0, Math.PI * 2);
                canvas.fill();
                canvas.closePath();
                canvas.fillStyle = '#555';
                canvas.textAlign = 'center';
                canvas.textBaseline = 'middle';
                canvas.font = 'bold 12px sans-serif';

                canvas.fillText(count, 22, 22, 40);

            }
        });

        let markers = new L.MarkerClusterGroup({
            maxClusterRadius: 60,
            iconCreateFunction: function (cluster) {
                let marker = cluster.getAllChildMarkers();
                return new temp_icon_cluster({marker: marker});
            },
            //Disable all of the defaults:
            spiderfyOnMaxZoom: false, showCoverageOnHover: false, zoomToBoundsOnClick: false
        });

        markers.on('clusterclick', function (a) {
            let markersArea = a.layer.getAllChildMarkers();
            let count = a.layer._childCount;
            let sum = [], st, i, counts = [], colors = [];
            //a.layer.unspiderfy();
            for (i = 0; i < 6; i++) {
                sum[i] = 0;
                counts[i] = 0;
                colors[i] = '';
            }
            for (i = 0; i < count; i++) {
                sum[markersArea[i].data.type] += markersArea[i].data.loss;
                counts[markersArea[i].data.type]++;
                colors[markersArea[i].data.type] = markersArea[i].data.color;
            }
            st = '<table style="text-align: center;"><tbody>';
            st += '<tr><td>Тип</td><td>Количество</td><td>Потери</td></tr>';
            for (i = 0; i < sum.length; i++) {
                if (counts[i])
                    st += "<tr><td style='background: " + colors[i] + "'></td><td>" + counts[i] + "</td>";
                if ((counts[i] > 0) && (i === 4))
                    st += "<td>Не учтено</td></tr>";
                else if (counts[i])
                    st += "<td>" + sum[i] + "</td></tr>";
            }
            st += "</tbody></table><input class='zoom' type='button' value='Увеличить'><input class='cluster' type='button' value='Кластер'>";
            let popup = new L.popup({
                className: "cluster_popup"
            })
                .setLatLng(a.latlng)
                .setContent(st)
                .openOn(map);
            $('.zoom').click(function () {
                map.closePopup();
                let bounds = new L.LatLngBounds(
                    a.layer._bounds._northEast,
                    a.layer._bounds._southWest);

                let zoomLevelBefore = map.getZoom();
                let zoomLevelAfter = map.getBoundsZoom(bounds, false, new L.Point(20, 20, null));

                // If the zoom level doesn't change
                if (zoomLevelAfter === zoomLevelBefore) {
                    // Send an event for the LeafletSpiderfier


                    map.setView(a.latlng, zoomLevelAfter + 1);
                } else {
                    map.fitBounds(bounds);
                }
            });
            $('.cluster').click(function () {
                a.layer.spiderfy();
                map.closePopup();
            });
        });
        return markers;
    }

    getResCluster() {
        let colors = ['#000000'];
        let temp_icon_cluster = L.Icon.extend({
            options: {
                iconSize: new L.Point(44, 44)
            },
            createIcon: function () {
                // based on L.Icon.Canvas from shramov/leaflet-plugins (BSD licence)
                let e = document.createElement('canvas');
                e.className = 'canvase_cluster';
                let s = this.options.iconSize;
                e.width = s.x;
                e.height = s.y;
                this.draw(e.getContext('2d'), s.x, s.y);
                return e;
            },
            draw: function (canvas, width, height) {

                let start = 0;
                let count = this.options.marker.length;
                let stats = [];
                let color_count = colors.length;
                for (let i = 0; i < color_count; ++i) {
                    stats[i] = 0;
                }
                for (let i = 0; i < count; ++i) {
                    stats[this.options.marker[i].category]++;
                }
                for (let i = 0; i < color_count; ++i) {
                    let size = stats[i] / count;


                    if (size > 0) {
                        canvas.beginPath();
                        canvas.moveTo(22, 22);
                        canvas.fillStyle = colors[i];
                        let from = start + 0.14,
                            to = start + size * Math.PI * 2;

                        if (to < from) {
                            from = start;
                        }
                        canvas.arc(22, 22, 22, from, to);

                        start = start + size * Math.PI * 2;
                        canvas.lineTo(22, 22);
                        canvas.fill();
                        canvas.closePath();
                    }

                }

                canvas.beginPath();
                canvas.fillStyle = 'white';
                canvas.arc(22, 22, 18, 0, Math.PI * 2);
                canvas.fill();
                canvas.closePath();
                canvas.fillStyle = '#555';
                canvas.textAlign = 'center';
                canvas.textBaseline = 'middle';
                canvas.font = 'bold 12px sans-serif';

                canvas.fillText(count, 22, 22, 40);

            }
        });

        let markers = new L.MarkerClusterGroup({
            maxClusterRadius: 60,
            iconCreateFunction: function (cluster) {
                let marker = cluster.getAllChildMarkers();
                return new temp_icon_cluster({marker: marker});
            },
            //Disable all of the defaults:
            spiderfyOnMaxZoom: false, showCoverageOnHover: false, zoomToBoundsOnClick: false
        });

        markers.on('clusterclick', function (a) {
            a.layer.spiderfy();
        });
        return markers;
    }


}