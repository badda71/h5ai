const {each, map, includes} = require('../util');
const server = require('../server');
const event = require('../core/event');
const allsettings = require('../core/settings');
const resource = require('../core/resource');

const settings = Object.assign({
	enabled: false,
	types: {
		img: ['img-bmp', 'img-gif', 'img-ico', 'img-jpg', 'img-png'],
		mov: ['vid-avi', 'vid-flv', 'vid-mkv', 'vid-mov', 'vid-mp4', 'vid-mpg', 'vid-webm'],
		doc: ['x-pdf', 'x-ps'] },
	delay: 1,
	size: 100,
	exif: false,
	chunksize: 20
}, allsettings.thumbnails);
const landscapeRatio = 4 / 3;


const queueItem = (queue, item) => {
    let type = null;

	for(let i in settings.types) {
		if (includes(settings.types[i], item.type)) {
			type = i; break;
		}
	}
	if (type==null) return;
	
    if (item.thumbSquare) {
        updateItem(item, item.thumbSquare, "square")
    } else {
        queue.push({
            type,
            href: item.absHref,
            ratio: 1,
            callback: src => {
                if (src && item.$view) updateItem(item, src, 'square');
            }
        });
    }

    if (item.thumbRational) {
        updateItem(item, item.thumbRational, 'landscape');
    } else {
        queue.push({
            type,
            href: item.absHref,
            ratio: landscapeRatio,
            callback: src => {
                if (src && item.$view) updateItem(item, src, 'landscape');
            }
        });
    }
};

const updateItem = (item, src, format='square') => {
	if (format=='square') item.thumbSquare = src;
	else item.thumbRational = src;
	const img=item.$view.find('.icon.'+format+' img');
	const icon=img.src;	
	img.addCls('thumb').attr('src', src);
	img.parent().addCls('icon-overlay');
};

const requestQueue = queue => {
    const thumbs = map(queue, req => {
        return {
            type: req.type,
            href: req.href,
            width: Math.round(settings.size * req.ratio),
            height: settings.size
        };
    });

    return server.request({
        action: 'get',
        thumbs
    }).then(json => {
        each(queue, (req, idx) => {
            req.callback(json && json.thumbs ? json.thumbs[idx] : null);
        });
    });
};

const breakAndRequestQueue = queue => {
    const len = queue.length;
    const chunksize = settings.chunksize;
    let p = Promise.resolve();
    for (let i = 0; i < len; i += chunksize) {
        p = p.then(() => requestQueue(queue.slice(i, i + chunksize)));
    }
};

const handleItems = items => {
    const queue = [];
    each(items, item => queueItem(queue, item));
    breakAndRequestQueue(queue);
};

const onViewChanged = added => {
    setTimeout(() => handleItems(added), settings.delay);
};

const init = () => {
    if (!settings.enabled) {
        return;
    }

	// generate my thumbnail overlay styles
	let styleHtml="";
	for(let i in settings.types) {
		let baseType = (settings.types[i][0]).split('-')[0];
		for (let i2 in settings.types[i]) {
			styleHtml+= `${i2!=0?',':''}
				#view.view-icons .icon-overlay.${settings.types[i][i2]}::before`;
		}
		styleHtml+= ` {
					position: absolute;
					top: 50%;
					left: 50%;
					transform: translate(-50%, -50%);
					-webkit-transform: translate(-50%, -50%);
					content: " ";
					z-index: 1;
					background: url(${resource.icon(baseType)}) bottom right no-repeat;
					width: 100%;
					height: 100%;
				}`;
	}
	let sheet = document.createElement('style');
	sheet.innerHTML = styleHtml;
	document.body.appendChild(sheet);

    event.sub('view.changed', onViewChanged);
};


init();
