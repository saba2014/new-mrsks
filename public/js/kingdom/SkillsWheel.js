    class SkillsWheel {

    constructor(numbers, colors, img, text) {
        this.numbers=[];
        this.colors = [];
        this.numbers = numbers;
        this.colors = colors;
        this.count = 0;
        this.text = text;
        if (img) this.img = img;
        else this.img = "img/icons/worker.svg";
        for (var i = 0; i < numbers.length; i++)
            if (numbers[i] > 0)
                this.count++;
    }

    createPic() {
        let numbers = this.numbers;
        let colors = this.colors;
        let count = this.count;
        let text = this.text;
        let background = new Image();
        background.src = this.img;
        /*background.onload = function(){
            ctx.drawImage(background,0,0);
        }*/
        let res = L.Icon.extend({
            options: {
                iconSize: new L.Point(44, 64)
            },
            draw(canvas, numbers, colors, count, text) {

                let start = 0;
                let color_count = colors.length;
                for (let i = 0; i < color_count; ++i) {
                    let size = 0;
                    if (numbers[i] !== 0) size = 1 / count;
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
                background.onload = function () {
                    canvas.drawImage(background, 11, 11, 22, 22);
                }
                if(text !== undefined) {
                    canvas.textAlign = 'center';
                    canvas.textBaseline = 'middle';
                    canvas.font = 'bold 32px sans-serif';
                    canvas.fillText(text, 24, 44, 40);
                }
            },
            createIcon() {
                let e = document.createElement('canvas');
                e.className = 'canvase_cluster';
                let s = new L.Point(44, 64);
                e.width = s.x;
                e.height = s.y;
                this.draw(e.getContext('2d'), numbers, colors, count, text);
                return e;
            }

        });

        return new res;

    }


}


