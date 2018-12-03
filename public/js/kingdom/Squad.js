class Squad {
    constructor(info) {
        this.name = info.name;
        this.img = info.iconPath;
        this.amount = 0;
        if (info.count) {
            if (info.count === -1)
                this.amount = '&#8734;';
            else this.amount = info.count;
        }
        this.inUse = 0;
        this.staff = info.staff;
        this.style = "";
        this.active = false;
        this.heroes = [];
        this.parent = info.parent;
        if (info.staff) {
            for (let i = 0; i < info.staff.length; i++) {
                info.staff[i]['iconPath'] = this.img;
                info.staff[i]['parent'] = this;
                let obj = new Hero(info.staff[i]);
                this.heroes.push(obj);
            }
        }
        if (info.default) {
            info.parent = this;
            let obj = new Hero(info);
            this.heroes.push(obj);
        }
    }

    decreaseAmount() {
        if (this.amount === '&#8734;') {
            return true;
        }
        if (this.amount > 0) {
            this.amount--;
            this.inUse++;
            /*for (let i = 0; i < this.heroes.length; i++)
                this.heroes[i].decreaseAmount();*/
            return true;
        }
        else return false;
    }

    returnOne() {
        if (this.amount === '&#8734;') {
            return true;
        }
        this.amount++;
        this.inUse--;
        this.parent.returnOne();
    }

    returnAmount() {
        if (this.amount === '&#8734;') {
            return true;
        }
        this.amount += this.inUse;
        this.inUse = 0;
        for (let i = 0; i < this.heroes.length; i++)
            this.heroes[i].returnAmount();
    }
}