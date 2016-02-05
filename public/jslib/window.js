Jx.DialogWindow = new Class({
    Extends: Jx.Dialog,

    outsideViewport: null,

    initialize: function(options, outsideViewport) {
        this.outsideViewport = outsideViewport;
        this.parent(options);
    },

    position: function(element, relative, options) {
        element = $(element);
        relative = $(relative);
        var hor = $splat(options.horizontal || ['center center']);
        var ver = $splat(options.vertical || ['center center']);
        var offsets = $merge({top:0,right:0,bottom:0,left:0}, options.offsets || {});
        
        var page;
        var scroll;
        if (!$(element.parentNode) || element.parentNode ==  document.body) {
            page = Element.getPageDimensions();
            scroll = $(document.body).getScroll();
        } else {
            page = $(element.parentNode).getContentBoxSize(); //width, height
            scroll = $(element.parentNode).getScroll();
        }
        var coords = relative.getCoordinates(); //top, left, width, height
        var size = element.getMarginBoxSize(); //width, height
        var left;
        var right;
        var top;
        var bottom;
        var n;
        if (!hor.some(function(opt) {
            var parts = opt.split(' ');
            if (parts.length != 2) {
                return false;
            }
            if (!isNaN(parseInt(parts[0],10))) {
                n = parseInt(parts[0],10);
                if (n>=0) {
                    left = n;                    
                } else {
                    left = coords.left + coords.width + n;
                }
            } else {
                switch(parts[0]) {
                    case 'right':
                        left = coords.left + coords.width;
                        break;
                    case 'center':
                        left = coords.left + Math.round(coords.width/2);
                        break;
                    case 'left':
                    default:
                        left = coords.left;
                        break;
                }                
            }
            if (!isNaN(parseInt(parts[1],10))) {
                n = parseInt(parts[1],10);
                if (n<0) {
                    right = left + n;
                    left = right - size.width;
                } else {
                    left += n;
                    right = left + size.width;
                }
                right = coords.left + coords.width + parseInt(parts[1],10);
                left = right - size.width;
            } else {
                switch(parts[1]) {
                    case 'left':
                        left -= offsets.left;
                        right = left + size.width;
                        break;
                    case 'right':
                        left += offsets.right;
                        right = left;
                        left = left - size.width;
                        break;
                    case 'center':
                    default:
                        left = left - Math.round(size.width/2);
                        right = left + size.width;
                        break;
                }                
            }
            return (left >= scroll.x && right <= scroll.x + page.width);
        })) {
            // all failed, snap the last position onto the page as best
            // we can - can't do anything if the element is wider than the
            // space available.
            if (!this.outsideViewport) {
                if (right > page.width) {
                    left = scroll.x + page.width - size.width;
                }
                if (left < 0) {
                    left = 0;
                }
            }
        }
        element.setStyle('left', left);
        
        if (!ver.some(function(opt) {
            var parts = opt.split(' ');
            if (parts.length != 2) {
                return false;
            }
            if (!isNaN(parseInt(parts[0],10))) {
                top = parseInt(parts[0],10);
            } else {
                switch(parts[0]) {
                    case 'bottom':
                        top = coords.top + coords.height;
                        break;
                    case 'center':
                        top = coords.top + Math.round(coords.height/2);
                        break;
                    case 'top':
                    default:
                        top = coords.top;
                        break;
                }
            }
            if (!isNaN(parseInt(parts[1],10))) {
                var n = parseInt(parts[1],10);
                if (n>=0) {
                    top += n;
                    bottom = top + size.height;
                } else {
                    bottom = top + n;
                    top = bottom - size.height; 
                }
            } else {
                switch(parts[1]) {
                    case 'top':
                        top -= offsets.top;
                        bottom = top + size.height;
                        break;
                    case 'bottom':
                        top += offsets.bottom;
                        bottom = top;
                        top = top - size.height;
                        break;
                    case 'center':
                    default:
                        top = top - Math.round(size.height/2);
                        bottom = top + size.height;
                        break;
                }                    
            }
            return (top >= scroll.y && bottom <= scroll.y + page.height);
        })) {
            // all failed, snap the last position onto the page as best
            // we can - can't do anything if the element is higher than the
            // space available.
            if (!this.outsideViewport) {
                if (bottom > page.height) {
                    top = scroll.y + page.height - size.height;
                }
                if (top < 0) {
                    top = 0;
                }
            }
        }
        element.setStyle('top', top);
        
        /* update the jx layout if necessary */
        var jxl = element.retrieve('jxLayout');
        if (jxl) {
            jxl.options.left = left;
            jxl.options.top = top;
        }
    }
});
