/**
 * Arxiu Monòver - Admin JavaScript
 * 
 * @package Arxiu_Monovar
 * @version 13.0.0
 */

(function($) {
    'use strict';

    // ═══════════════════════════════════════════════════════════════════════════
    // OBJECTE PRINCIPAL
    // ═══════════════════════════════════════════════════════════════════════════

    window.ArxiuAdmin = {
        
        // State
        model: null,
        modelLoading: false,
        modelReady: false,
        tags: [],
        mediaFrame: null,
        currentImageId: null,
        descriptionData: {},
        sliderExact: null,
        sliderRange: null,
        map: null,
        selectedSlugs: new Set(),
        allFeaturesMap: new Map(),
        
        // ═══════════════════════════════════════════════════════════════════════
        // INICIALITZACIÓ
        // ═══════════════════════════════════════════════════════════════════════
        
        init: function() {
            var self = this;
            
            // Verificar config
            if (typeof ArxiuConfig === 'undefined') {
                console.warn('ArxiuConfig no definit');
                return;
            }
            
            // Carregar dades existents
            this.currentImageId = $('#arxiu-image-id').val() || null;
            
            var tagsData = $('#arxiu-tags-data').val();
            if (tagsData) {
                try { this.tags = JSON.parse(tagsData); } catch(e) {}
            }
            
            var descData = $('#arxiu-description-data').val();
            if (descData) {
                try { this.descriptionData = JSON.parse(descData); } catch(e) {}
            }
            
            // Bind events
            this.bindImageEvents();
            this.bindDetectionEvents();
            this.bindAnalysisEvents();
            this.bindGenreEvents();
            this.bindDateEvents();
            this.bindLocationEvents();
            
            // Inicialitzar components
            this.initDateSliders();
            this.initMapbox();
            
            // Carregar model TensorFlow si hi ha imatge
            if (this.currentImageId && $('#arxiu-detect-persons').length) {
                this.initTensorFlow();
            }
            
            // Renderitzar pins existents
            if (this.tags.length > 0) {
                this.renderPins();
            }
            
            // Carregar info de quota (v17.0)
            this.loadQuotaInfo();
            
            console.log('✓ ArxiuAdmin v17.0 inicialitzat');
        },
        
        // ═══════════════════════════════════════════════════════════════════════
        // QUOTA API (NOU v17.0)
        // ═══════════════════════════════════════════════════════════════════════
        
        loadQuotaInfo: function() {
            var self = this;
            
            $.ajax({
                url: ArxiuConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'arxiu_get_quota',
                    nonce: ArxiuConfig.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.renderQuotaIndicator(response.data);
                    }
                }
            });
        },
        
        renderQuotaIndicator: function(quota) {
            var $indicator = $('#arxiu-quota-indicator');
            if (!$indicator.length) {
                // Crear indicador si no existeix
                $indicator = $('<div id="arxiu-quota-indicator" class="arxiu-quota-bar"></div>');
                $('.arxiu-metabox-content').first().prepend($indicator);
            }
            
            var percent = quota.percent_used || 0;
            var colorClass = percent < 50 ? 'green' : (percent < 80 ? 'yellow' : 'red');
            
            $indicator.html(
                '<div class="arxiu-quota-label">' +
                '📊 Quota Gemini: ' + quota.calls_today + ' / ' + quota.limit_daily + ' (' + percent + '%)' +
                '</div>' +
                '<div class="arxiu-quota-progress ' + colorClass + '" style="width: ' + percent + '%"></div>'
            );
        },
        
        // ═══════════════════════════════════════════════════════════════════════
        // IMATGE: UPLOAD I GESTIÓ
        // ═══════════════════════════════════════════════════════════════════════
        
        bindImageEvents: function() {
            var self = this;
            
            // Seleccionar imatge
            $(document).on('click', '#arxiu-select-image, #arxiu-image-placeholder', function(e) {
                e.preventDefault();
                self.openMediaFrame();
            });
            
            // Eliminar imatge
            $(document).on('click', '#arxiu-remove-image', function(e) {
                e.preventDefault();
                if (confirm(ArxiuConfig.i18n.confirmRemoveImage || 'Eliminar la imatge?')) {
                    self.removeImage();
                }
            });
        },
        
        openMediaFrame: function() {
            var self = this;
            
            if (this.mediaFrame) {
                this.mediaFrame.open();
                return;
            }
            
            this.mediaFrame = wp.media({
                title: 'Selecciona una imatge',
                button: { text: 'Usar imatge' },
                multiple: false,
                library: { type: 'image' }
            });
            
            this.mediaFrame.on('select', function() {
                var attachment = self.mediaFrame.state().get('selection').first().toJSON();
                self.setImage(attachment);
            });
            
            this.mediaFrame.open();
        },
        
        setImage: function(attachment) {
            var self = this;
            var $container = $('#arxiu-image-container');
            var imageUrl = attachment.sizes && attachment.sizes.large 
                ? attachment.sizes.large.url 
                : attachment.url;
            
            // Estructura: overlay DESPRÉS de la imatge, tots dos dins d'un wrapper relatiu
            var html = '<div class="arxiu-image-preview" id="arxiu-image-preview">' +
                '<img src="' + imageUrl + '" id="arxiu-image-element" crossorigin="anonymous">' +
                '<div class="arxiu-image-overlay" id="arxiu-image-overlay"></div>' +
            '</div>';
            
            $container.html(html).addClass('has-image');
            
            // Actualitzar camps
            $('#arxiu-image-id').val(attachment.id);
            this.currentImageId = attachment.id;
            
            // Actualitzar botons
            $('#arxiu-select-image').html('<span class="dashicons dashicons-upload"></span> Canviar imatge');
            
            // Afegir botó eliminar si no existeix
            if (!$('#arxiu-remove-image').length) {
                $('#arxiu-select-image').after(
                    '<button type="button" class="button" id="arxiu-remove-image">' +
                    '<span class="dashicons dashicons-trash"></span> Eliminar</button>'
                );
            }
            
            // Mostrar seccions ocultes
            $('.arxiu-section-detection, .arxiu-section-analysis').show();
            
            // Netejar tags anteriors
            this.tags = [];
            $('#arxiu-tags-data').val('[]');
            $('#arxiu-detection-count').text('0 etiquetes');
            
            // Notificar a altres mòduls
            $(document).trigger('arxiu_image_selected', [attachment.id]);
            
            // DETECCIÓ AUTOMÀTICA: Esperar que la imatge carregui completament
            var $img = $('#arxiu-image-element');
            
            function onImageReady() {
                // Petit delay per assegurar que el navegador ha renderitzat la imatge
                setTimeout(function() {
                    var imgWidth = $img[0].offsetWidth;
                    var imgHeight = $img[0].offsetHeight;
                    
                    console.log('🖼️ Imatge carregada:', $img[0].naturalWidth + 'x' + $img[0].naturalHeight);
                    console.log('🖼️ Imatge renderitzada:', imgWidth + 'x' + imgHeight);
                    
                    // ═══════════════════════════════════════════════════════════════
                    // EXECUCIÓ AUTOMÀTICA DE TOTES LES DETECCIONS
                    // ═══════════════════════════════════════════════════════════════
                    
                    console.log('🚀 Iniciant deteccions automàtiques...');
                    
                    // 1. DETECCIÓ DE PERSONES (TensorFlow)
                    if (!self.modelReady && !self.modelLoading) {
                        console.log('📍 1/4 - Carregant model TensorFlow...');
                        self.initTensorFlow(function() {
                            console.log('✅ Model carregat, executant detecció...');
                            self.runDetection();
                        });
                    } else if (self.modelReady) {
                        console.log('📍 1/3 - Executant detecció de persones...');
                        setTimeout(function() {
                            self.runDetection();
                        }, 100);
                    }
                    
                    // 2. DETECCIÓ DE DATA (des del títol/nom del fitxer) - LOCAL
                    setTimeout(function() {
                        console.log('📍 2/3 - Detectant data (local)...');
                        self.autoDetectDate();
                    }, 300);
                    
                    // 3. VERIFICAR ANÀLISI PRE-EXISTENT (v17.2)
                    // Primer comprovem si ja existeix anàlisi guardada (processada en background)
                    setTimeout(function() {
                        console.log('📍 3/3 - Comprovant anàlisi pre-existent...');
                        self.checkPreAnalysis(attachment.id, function(hasPreAnalysis) {
                            if (!hasPreAnalysis && ArxiuConfig.hasGeminiKey) {
                                console.log('📍 3/3 - No hi ha anàlisi prèvia, analitzant amb Gemini...');
                                self.analyzeComplete(false);
                            } else if (!hasPreAnalysis && !ArxiuConfig.hasGeminiKey) {
                                console.log('⏭️ 3/3 - Gemini no disponible (sense API key)');
                            }
                            // Si hasPreAnalysis=true, les dades ja s'han carregat
                        });
                    }, 500);
                    
                }, 100); // Fi del setTimeout inicial
            }
            
            // Esperar càrrega completa
            if ($img[0].complete && $img[0].naturalWidth > 0) {
                onImageReady();
            } else {
                $img.on('load', onImageReady);
            }
        },
        
        removeImage: function() {
            var $container = $('#arxiu-image-container');
            
            // Restaurar placeholder
            $container.html(
                '<div class="arxiu-image-placeholder" id="arxiu-image-placeholder">' +
                    '<span class="dashicons dashicons-format-image"></span>' +
                    '<p>Fes clic per seleccionar una imatge</p>' +
                '</div>'
            ).removeClass('has-image');
            
            $('#arxiu-image-id').val('');
            this.currentImageId = null;
            this.tags = [];
            $('#arxiu-tags-data').val('[]');
            
            $('#arxiu-select-image').html('<span class="dashicons dashicons-upload"></span> Seleccionar imatge');
            $('#arxiu-remove-image').remove();
            
            // Ocultar seccions
            $('.arxiu-section-detection, .arxiu-section-analysis').hide();
        },
        
        // ═══════════════════════════════════════════════════════════════════════
        // TENSORFLOW: DETECCIÓ DE PERSONES
        // ═══════════════════════════════════════════════════════════════════════
        
        initTensorFlow: function(callback) {
            var self = this;
            
            // Guardar callback per executar quan model estigui llest
            if (callback) {
                this.onModelReadyCallback = callback;
            }
            
            if (this.modelReady) {
                if (this.onModelReadyCallback) {
                    this.onModelReadyCallback();
                    this.onModelReadyCallback = null;
                }
                return;
            }
            
            if (this.modelLoading) return;
            this.modelLoading = true;
            
            this.updateStatus('#arxiu-model-status', 'loading', 'Carregant IA...');
            
            // Carregar scripts
            this.loadScript(ArxiuConfig.tensorflowUrl, function() {
                self.loadScript(ArxiuConfig.cocoSsdUrl, function() {
                    self.loadModel();
                });
            });
        },
        
        loadScript: function(url, callback) {
            // Evitar carregar scripts duplicats
            var existing = document.querySelector('script[src="' + url + '"]');
            if (existing) {
                if (callback) callback();
                return;
            }
            
            var script = document.createElement('script');
            script.src = url;
            script.onload = callback;
            script.onerror = function() {
                console.error('Error carregant script:', url);
            };
            document.head.appendChild(script);
        },
        
        loadModel: function() {
            var self = this;
            
            if (typeof cocoSsd === 'undefined') {
                this.updateStatus('#arxiu-model-status', 'error', 'Error: COCO-SSD no disponible');
                return;
            }
            
            // Usar model més precís
            cocoSsd.load({ base: 'mobilenet_v2' }).then(function(model) {
                self.model = model;
                self.modelReady = true;
                self.modelLoading = false;
                
                self.updateStatus('#arxiu-model-status', 'ready', '✓ Model IA llest');
                $('#arxiu-detect-persons').prop('disabled', false);
                
                // Executar callback si existeix
                if (self.onModelReadyCallback) {
                    setTimeout(function() {
                        self.onModelReadyCallback();
                        self.onModelReadyCallback = null;
                    }, 100);
                }
            }).catch(function(err) {
                self.modelLoading = false;
                self.updateStatus('#arxiu-model-status', 'error', 'Error carregant model');
                console.error(err);
            });
        },
        
        bindDetectionEvents: function() {
            var self = this;
            
            // Detectar persones
            $(document).on('click', '#arxiu-detect-persons', function(e) {
                e.preventDefault();
                if (self.modelReady) {
                    self.runDetection();
                }
            });
            
            // Afegir manual
            $(document).on('click', '#arxiu-add-manual-tag', function(e) {
                e.preventDefault();
                self.enableManualTagMode();
            });
            
            // Esborrar tots (botó que no existeix al template, però per si s'afegeix)
            $(document).on('click', '#arxiu-clear-tags', function(e) {
                e.preventDefault();
                self.clearAllTags();
            });
        },
        
        runDetection: function() {
            var self = this;
            var $img = $('#arxiu-image-element');
            var $overlay = $('#arxiu-image-overlay');
            
            if (!$img.length || !this.model) return;
            
            // Esperar que la imatge estigui completament renderitzada
            if (!$img[0].complete || $img[0].naturalWidth === 0) {
                console.log('⏳ Esperant que la imatge carregui...');
                $img.on('load', function() {
                    self.runDetection();
                });
                return;
            }
            
            // L'overlay ara usa right:0, bottom:0 en CSS - no cal ajustar manualment
            console.log('📐 Imatge:', $img[0].offsetWidth + 'x' + $img[0].offsetHeight);
            
            // Mostrar loading overlay
            $overlay.addClass('detecting');
            $('#arxiu-detect-persons').prop('disabled', true).addClass('loading');
            this.updateStatus('#arxiu-model-status', 'loading', '🔍 Analitzant...');
            
            // Usar threshold més baix per detectar més persones
            var minConfidence = 0.35;
            
            this.model.detect($img[0]).then(function(predictions) {
                var persons = predictions.filter(function(p) {
                    return p.class === 'person' && p.score >= minConfidence;
                });
                
                // Ordenar per confiança
                persons.sort(function(a, b) { return b.score - a.score; });
                
                $overlay.removeClass('detecting');
                $('#arxiu-detect-persons').prop('disabled', false).removeClass('loading');
                
                if (persons.length > 0) {
                    self.updateStatus('#arxiu-model-status', 'ready', '✓ ' + persons.length + ' persones');
                    self.renderSuggestions(persons, $img, $overlay);
                    $('#arxiu-detection-count').text(persons.length + ' detectades');
                } else {
                    self.updateStatus('#arxiu-model-status', 'ready', 'Cap persona detectada');
                    $('#arxiu-detection-count').text('0 detectades');
                }
                
            }).catch(function(err) {
                $overlay.removeClass('detecting');
                $('#arxiu-detect-persons').prop('disabled', false).removeClass('loading');
                self.updateStatus('#arxiu-model-status', 'error', 'Error en anàlisi');
                console.error(err);
            });
        },
        
        renderSuggestions: function(persons, $img, $overlay) {
            var self = this;
            
            // IMPORTANT: Afegir a l'overlay (com el codi antic que funcionava)
            var $overlay = $('#arxiu-image-overlay');
            
            // Eliminar suggeriments anteriors
            $overlay.find('.arxiu-suggestion').remove();
            
            // Dimensions NATURALS de la imatge (originals)
            var naturalWidth = $img[0].naturalWidth;
            var naturalHeight = $img[0].naturalHeight;
            
            console.log('═══════════════════════════════════════');
            console.log('📐 Imatge NATURAL:', naturalWidth + 'x' + naturalHeight);
            console.log('═══════════════════════════════════════');
            
            persons.forEach(function(person, index) {
                // bbox = [x, y, width, height] en píxels de la imatge NATURAL
                var bbox = person.bbox;
                
                // Convertir a PERCENTATGES (com el codi antic que funcionava!)
                var leftPercent = (bbox[0] / naturalWidth) * 100;
                var topPercent = (bbox[1] / naturalHeight) * 100;
                var widthPercent = (bbox[2] / naturalWidth) * 100;
                var heightPercent = (bbox[3] / naturalHeight) * 100;
                
                // Centre en percentatge per guardar
                var centerXPercent = leftPercent + (widthPercent / 2);
                var centerYPercent = topPercent + (heightPercent / 3); // 1/3 des de dalt (com l'antic)
                
                // Evitar duplicats amb tags existents
                var isDuplicate = self.tags.some(function(tag) {
                    return Math.abs(tag.x - centerXPercent) < 5 &&
                           Math.abs(tag.y - centerYPercent) < 5;
                });
                
                if (isDuplicate) {
                    return;
                }
                
                var confidence = (person.score * 100).toFixed(0);
                
                console.log('📍 Persona', index + 1, ':', 
                    'left:', leftPercent.toFixed(1) + '%',
                    'top:', topPercent.toFixed(1) + '%',
                    'size:', widthPercent.toFixed(1) + '%' + ' x ' + heightPercent.toFixed(1) + '%',
                    '(' + confidence + '%)');
                
                // Crear suggestion amb PERCENTATGES (EXACTAMENT com l'antic)
                var $suggestion = $('<div class="arxiu-suggestion pulse"></div>')
                    .css({
                        left: leftPercent + '%',
                        top: topPercent + '%',
                        width: widthPercent + '%',
                        height: heightPercent + '%'
                    })
                    .data('centerX', centerXPercent)
                    .data('centerY', centerYPercent)
                    .data('confidence', confidence)
                    .on('click', function(e) {
                        e.stopPropagation();
                        var x = $(this).data('centerX');
                        var y = $(this).data('centerY');
                        self.showNameDialog(x, y, $(this), e);
                    });
                
                $overlay.append($suggestion);
            });
            
            console.log('✅ ' + $overlay.find('.arxiu-suggestion').length + ' suggeriments renderitzats');
        },
        
        /**
         * Mostra diàleg per introduir nom de la persona
         */
        showNameDialog: function(x, y, $box, event) {
            var self = this;
            
            // Tancar diàlegs anteriors
            $('.arxiu-name-dialog').remove();
            
            var $dialog = $('<div class="arxiu-name-dialog">' +
                '<div class="arxiu-name-dialog-content">' +
                    '<label>Nom de la persona:</label>' +
                    '<input type="text" class="arxiu-name-input" placeholder="Escriu el nom..." autofocus>' +
                    '<div class="arxiu-name-dialog-buttons">' +
                        '<button type="button" class="button button-primary arxiu-name-save">Guardar</button>' +
                        '<button type="button" class="button arxiu-name-cancel">Cancel·lar</button>' +
                    '</div>' +
                '</div>' +
            '</div>');
            
            // Posicionar a prop del clic
            $dialog.css({
                position: 'fixed',
                left: event.clientX + 'px',
                top: event.clientY + 'px',
                zIndex: 100001
            });
            
            $('body').append($dialog);
            
            // Focus a l'input
            setTimeout(function() {
                $dialog.find('.arxiu-name-input').focus();
            }, 50);
            
            // Ajustar posició si surt de pantalla
            var dialogRect = $dialog[0].getBoundingClientRect();
            if (dialogRect.right > window.innerWidth) {
                $dialog.css('left', (window.innerWidth - dialogRect.width - 20) + 'px');
            }
            if (dialogRect.bottom > window.innerHeight) {
                $dialog.css('top', (event.clientY - dialogRect.height - 10) + 'px');
            }
            
            // Guardar amb botó o Enter
            function saveTag() {
                var name = $dialog.find('.arxiu-name-input').val().trim();
                if (name) {
                    self.addTag(x, y, name);
                    $box.fadeOut(200, function() { $(this).remove(); });
                    $dialog.remove();
                } else {
                    $dialog.find('.arxiu-name-input').addClass('error').focus();
                }
            }
            
            $dialog.find('.arxiu-name-save').on('click', saveTag);
            $dialog.find('.arxiu-name-input').on('keypress', function(e) {
                if (e.which === 13) { // Enter
                    saveTag();
                }
            });
            
            // Cancel·lar
            $dialog.find('.arxiu-name-cancel').on('click', function() {
                $dialog.remove();
            });
            
            // Tancar si es clica fora
            setTimeout(function() {
                $(document).one('click', function(e) {
                    if (!$(e.target).closest('.arxiu-name-dialog').length) {
                        $dialog.remove();
                    }
                });
            }, 100);
        },
        
        addTag: function(x, y, label) {
            label = label || 'Persona ' + (this.tags.length + 1);
            
            this.tags.push({ x: x, y: y, label: label });
            this.saveTags();
            this.renderPins();
            
            $('#arxiu-clear-tags').show();
        },
        
        renderPins: function() {
            var self = this;
            var $overlay = $('#arxiu-image-overlay');
            var $img = $('#arxiu-image-element');
            
            // Eliminar pins anteriors
            $overlay.find('.arxiu-pin').remove();
            
            if (!$img.length || this.tags.length === 0) {
                $('#arxiu-detection-count').text('0 etiquetes');
                return;
            }
            
            // Obtenir dimensions de la imatge
            var displayWidth = $img[0].offsetWidth;
            var displayHeight = $img[0].offsetHeight;
            
            this.tags.forEach(function(tag, index) {
                // Les coordenades estan en percentatge
                var left = (tag.x / 100) * displayWidth;
                var top = (tag.y / 100) * displayHeight;
                
                var $pin = $('<div class="arxiu-pin"></div>')
                    .css({ 
                        position: 'absolute',
                        left: left + 'px', 
                        top: top + 'px' 
                    })
                    .attr('title', tag.label)
                    .data('index', index);
                
                // Afegir label
                var $label = $('<span class="arxiu-pin-label">' + tag.label + '</span>');
                $pin.append($label);
                
                // Draggable
                $pin.draggable({
                    containment: '#arxiu-image-preview',
                    stop: function(event, ui) {
                        // Convertir nova posició a percentatge
                        var newX = (ui.position.left / displayWidth) * 100;
                        var newY = (ui.position.top / displayHeight) * 100;
                        
                        self.tags[index].x = newX;
                        self.tags[index].y = newY;
                        self.saveTags();
                    }
                });
                
                // Click per editar
                $pin.on('click', function(e) {
                    if (!$(this).hasClass('ui-draggable-dragging')) {
                        self.openEditDialog(index, e);
                    }
                });
                
                $overlay.append($pin);
            });
            
            // Actualitzar comptador
            $('#arxiu-detection-count').text(this.tags.length + ' etiquetes');
        },
        
        openEditDialog: function(index, event) {
            var self = this;
            var tag = this.tags[index];
            
            // Tancar altres popovers
            $('.arxiu-popover').remove();
            
            var $popover = $('<div class="arxiu-popover">' +
                '<input type="text" class="arxiu-popover-input" value="' + tag.label + '">' +
                '<div class="arxiu-popover-buttons">' +
                    '<button type="button" class="button button-primary arxiu-popover-save">Desar</button>' +
                    '<button type="button" class="button arxiu-popover-delete">Eliminar</button>' +
                '</div>' +
            '</div>');
            
            $popover.css({
                left: event.pageX + 10,
                top: event.pageY - 20
            });
            
            $('body').append($popover);
            $popover.find('input').focus().select();
            
            // Desar
            $popover.find('.arxiu-popover-save').on('click', function() {
                var newLabel = $popover.find('input').val().trim();
                if (newLabel) {
                    self.tags[index].label = newLabel;
                    self.saveTags();
                    self.renderPins();
                }
                $popover.remove();
            });
            
            // Eliminar
            $popover.find('.arxiu-popover-delete').on('click', function() {
                self.tags.splice(index, 1);
                self.saveTags();
                self.renderPins();
                $popover.remove();
                
                if (self.tags.length === 0) {
                    $('#arxiu-clear-tags').hide();
                }
            });
            
            // Tancar amb click fora
            setTimeout(function() {
                $(document).one('click', function(e) {
                    if (!$(e.target).closest('.arxiu-popover, .arxiu-pin').length) {
                        $popover.remove();
                    }
                });
            }, 100);
        },
        
        enableManualTagMode: function() {
            var self = this;
            var $preview = $('#arxiu-image-preview');
            
            $preview.addClass('manual-mode').css('cursor', 'crosshair');
            this.updateLog('#arxiu-detection-log', 'info', '👆 Fes clic a la imatge per afegir una etiqueta');
            
            $preview.one('click', function(e) {
                if ($(e.target).hasClass('arxiu-pin') || $(e.target).hasClass('arxiu-suggestion')) {
                    return;
                }
                
                var offset = $(this).offset();
                var x = ((e.pageX - offset.left) / $(this).width()) * 100;
                var y = ((e.pageY - offset.top) / $(this).height()) * 100;
                
                self.addTag(x, y);
                $preview.removeClass('manual-mode').css('cursor', '');
                self.updateLog('#arxiu-detection-log', 'success', '✓ Etiqueta afegida');
            });
        },
        
        clearAllTags: function() {
            this.tags = [];
            this.saveTags();
            this.renderPins();
            $('#arxiu-detection-count').text('0 etiquetes');
            $('#arxiu-image-overlay .arxiu-suggestion').remove();
        },
        
        saveTags: function() {
            $('#arxiu-tags-data').val(JSON.stringify(this.tags));
        },
        
        // ═══════════════════════════════════════════════════════════════════════
        // VERIFICACIÓ ANÀLISI PRE-EXISTENT (v17.2)
        // ═══════════════════════════════════════════════════════════════════════
        
        /**
         * Comprova si l'attachment ja té anàlisi guardada (processada en background)
         */
        checkPreAnalysis: function(attachmentId, callback) {
            var self = this;
            
            $.ajax({
                url: ArxiuConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'arxiu_get_attachment_analysis',
                    nonce: ArxiuConfig.nonce,
                    attachment_id: attachmentId
                },
                success: function(response) {
                    if (response.success && response.data && response.data._is_pre_analyzed) {
                        console.log('✅ Anàlisi pre-existent trobada!', response.data);
                        
                        // Renderitzar els resultats
                        self.renderAnalysisResults(response.data);
                        
                        // Mostrar avís que les dades venen de processament previ
                        self.updateLog('#arxiu-analysis-log', 'success', 
                            '✅ Dades carregades d\'anàlisi prèvia (processada en background)');
                        
                        // Quota info si disponible
                        if (response.data._quota) {
                            console.log('📊 Quota:', response.data._quota.calls_today + '/' + response.data._quota.limit_daily);
                        }
                        
                        callback(true);
                    } else {
                        console.log('ℹ️ No hi ha anàlisi pre-existent');
                        callback(false);
                    }
                },
                error: function() {
                    console.log('⚠️ Error comprovant anàlisi pre-existent');
                    callback(false);
                }
            });
        },
        
        // ═══════════════════════════════════════════════════════════════════════
        // ANÀLISI GEMINI (UNIFICADA v17.0)
        // ═══════════════════════════════════════════════════════════════════════
        
        bindAnalysisEvents: function() {
            var self = this;
            
            $(document).on('click', '#arxiu-analyze-content', function(e) {
                e.preventDefault();
                self.analyzeComplete(false);
            });
            
            $(document).on('click', '#arxiu-reanalyze-content', function(e) {
                e.preventDefault();
                self.analyzeComplete(true);
            });
            
            // Auto-save quan es modifica
            $(document).on('blur', '#arxiu-visual-tags, #arxiu-description-text', function() {
                self.saveDescriptionData();
            });
        },
        
        /**
         * ANÀLISI UNIFICADA v17.0
         * Una sola crida a Gemini que retorna: gènere + tags + data + ubicació
         */
        analyzeComplete: function(forceRefresh) {
            var self = this;
            
            if (!this.currentImageId) {
                this.updateLog('#arxiu-analysis-log', 'warning', 'Selecciona una imatge primer');
                return;
            }
            
            // Actualitzar UI - mostrar que estem analitzant
            this.updateLog('#arxiu-analysis-log', 'info', '🔍 Anàlisi unificada amb Gemini...');
            this.updateLog('#arxiu-genre-log', 'info', '⏳ Esperant anàlisi...');
            $('#arxiu-analyze-content').prop('disabled', true);
            $('#arxiu-classify-genre').prop('disabled', true);
            
            $.ajax({
                url: ArxiuConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'arxiu_analyze_image',
                    nonce: ArxiuConfig.nonce,
                    image_id: this.currentImageId,
                    title: $('#title').val() || '',
                    force_refresh: forceRefresh ? 'true' : 'false'
                },
                success: function(response) {
                    if (response.success) {
                        self.descriptionData = response.data;
                        self.renderAnalysisResults(response.data);
                        
                        // Mostrar quota si disponible
                        var quotaMsg = '';
                        if (response.data._quota) {
                            var q = response.data._quota;
                            quotaMsg = ' (Quota: ' + q.calls_today + '/' + q.limit_daily + ')';
                        }
                        
                        self.updateLog('#arxiu-analysis-log', 'success', 
                            (response.data._from_cache ? '✓ Resultats (caché)' : '✓ Anàlisi completada') + quotaMsg);
                    } else {
                        self.updateLog('#arxiu-analysis-log', 'error', response.data.message || 'Error');
                        self.updateLog('#arxiu-genre-log', 'error', response.data.message || 'Error');
                    }
                },
                error: function() {
                    self.updateLog('#arxiu-analysis-log', 'error', 'Error de connexió');
                    self.updateLog('#arxiu-genre-log', 'error', 'Error de connexió');
                },
                complete: function() {
                    $('#arxiu-analyze-content').prop('disabled', false);
                    $('#arxiu-classify-genre').prop('disabled', false);
                }
            });
        },
        
        renderAnalysisResults: function(data) {
            var self = this;
            var $results = $('#arxiu-analysis-results');
            var $tagsContainer = $('#arxiu-visual-tags');
            
            // ═══════════════════════════════════════════════════════════════════
            // GÈNERE (NOU v17.0 - Ve inclòs en la resposta unificada)
            // ═══════════════════════════════════════════════════════════════════
            if (data.genre && data.genre_term_id) {
                console.log('🎭 Gènere detectat:', data.genre, '(ID:', data.genre_term_id + ')');
                
                // Deseleccionar tots
                $('.arxiu-genre-btn').removeClass('selected').find('input').prop('checked', false);
                
                // Seleccionar el correcte
                var $target = $('.arxiu-genre-btn input[value="' + data.genre_term_id + '"]');
                if ($target.length) {
                    $target.prop('checked', true).closest('.arxiu-genre-btn').addClass('selected');
                    this.updateLog('#arxiu-genre-log', 'success', '✓ Gènere: ' + (data.genre_term_name || data.genre));
                }
                
                // Actualitzar resultat del metabox de gèneres (v17.1)
                var $genreResult = $('#arxiu-genre-result');
                if ($genreResult.length) {
                    $genreResult
                        .removeClass('loading error')
                        .addClass('success')
                        .html('✅ Classificat com: <strong>' + (data.genre_term_name || data.genre) + '</strong>')
                        .show();
                }
            }
            
            // Tags - renderitzar com a spans
            if (data.tags_val && Array.isArray(data.tags_val)) {
                var tagsHtml = data.tags_val.map(function(tag) {
                    return '<span class="arxiu-tag">' + tag + '</span>';
                }).join('');
                $tagsContainer.html(tagsHtml);
            }
            
            // Summary
            if (data.summary) {
                $('#arxiu-description-text').text(data.summary);
            }
            
            // Ubicació detectada (compatible amb format antic i nou)
            var ubic = data.ubicacio_estimate;
            if (ubic && (ubic.poi_nom || ubic.lloc_nom || ubic.barri_nom)) {
                // Normalitzar format (suportar tant lloc_slug com poi_slug)
                var poiSlug = ubic.poi_slug || ubic.lloc_slug || '';
                var poiNom = ubic.poi_nom || ubic.lloc_nom || '';
                var barriSlug = ubic.barri_slug || '';
                var barriNom = ubic.barri_nom || '';
                var displayName = poiNom || barriNom || '';
                var confidence = ubic.confidence || 'medium';
                
                console.log('📍 Ubicació detectada:', { poi: poiSlug, barri: barriSlug, nom: displayName });
                
                var ubicHtml = '<div class="arxiu-result-group" id="arxiu-ubicacio-result">' +
                    '<label>📍 Ubicació detectada</label>' +
                    '<div class="arxiu-ubicacio-suggestion">' +
                    '<span class="arxiu-detected-value">';
                
                // Mostrar POI i/o Barri
                if (poiNom) {
                    ubicHtml += '<strong>' + poiNom + '</strong>';
                    if (barriNom) {
                        ubicHtml += ' <small>(' + barriNom + ')</small>';
                    }
                } else if (barriNom) {
                    ubicHtml += '<strong>' + barriNom + '</strong>';
                }
                
                ubicHtml += ' <span class="arxiu-confidence-badge arxiu-confidence-' + confidence + '">' + confidence.toUpperCase() + '</span></span>';
                
                // Raonament
                if (ubic.reason) {
                    ubicHtml += '<div class="arxiu-ubicacio-reason"><small>💡 ' + ubic.reason + '</small></div>';
                }
                
                // Elements identificats
                if (ubic.elements_identificats && ubic.elements_identificats.length > 0) {
                    ubicHtml += '<div class="arxiu-ubicacio-elements"><small>🔍 Elements: ' + ubic.elements_identificats.join(', ') + '</small></div>';
                }
                
                // Botons d'acció - SEMPRE mostrar si tenim un slug
                ubicHtml += '<div class="arxiu-ubicacio-actions">';
                
                if (poiSlug || barriSlug) {
                    ubicHtml += '<button type="button" class="button button-primary button-small arxiu-apply-ubicacio-btn" ' +
                        'data-poi="' + poiSlug + '" data-barri="' + barriSlug + '" title="Aplicar al mapa">' +
                        '<span class="dashicons dashicons-location"></span> Aplicar al mapa</button> ';
                    
                    // Botó per fer scroll al mapa
                    ubicHtml += '<button type="button" class="button button-small arxiu-scroll-map-btn" title="Anar al mapa">' +
                        '<span class="dashicons dashicons-visibility"></span> Veure mapa</button>';
                }
                
                ubicHtml += '</div></div>'; // tancar suggestion
                
                // Alternatives (si n'hi ha)
                if (data.ubicacio_alternatives && data.ubicacio_alternatives.length > 0) {
                    ubicHtml += '<div class="arxiu-ubicacio-alternatives"><small><strong>Alternatives:</strong></small><ul>';
                    data.ubicacio_alternatives.forEach(function(alt) {
                        var altPoiSlug = alt.poi_slug || alt.lloc_slug || '';
                        var altBarriSlug = alt.barri_slug || '';
                        var altName = alt.poi_nom || alt.lloc_nom || alt.barri_nom || altPoiSlug || altBarriSlug;
                        var altConfidence = alt.confidence || 'low';
                        
                        ubicHtml += '<li>' + altName + 
                            ' <span class="arxiu-confidence-badge arxiu-confidence-' + altConfidence + '">' + altConfidence.toUpperCase() + '</span>';
                        
                        // Botó per aplicar alternativa
                        if (altPoiSlug || altBarriSlug) {
                            ubicHtml += ' <button type="button" class="button button-small arxiu-apply-ubicacio-btn" ' +
                                'data-poi="' + altPoiSlug + '" data-barri="' + altBarriSlug + '">' +
                                'Aplicar</button>';
                        }
                        
                        if (alt.reason) {
                            ubicHtml += ' <small>(' + alt.reason + ')</small>';
                        }
                        ubicHtml += '</li>';
                    });
                    ubicHtml += '</ul></div>';
                }
                
                ubicHtml += '</div>'; // tancar result-group
                
                if (!$('#arxiu-ubicacio-result').length) {
                    $results.append(ubicHtml);
                } else {
                    $('#arxiu-ubicacio-result').replaceWith(ubicHtml);
                }
                
                // Vincular events dels botons
                this.bindApplyUbicacioButtons();
                this.bindScrollMapButton();
            }
            
            // Festa
            if (data.festa_detected && data.festa_detected.festa_nom) {
                var festa = data.festa_detected;
                var festaHtml = '<div class="arxiu-result-group arxiu-result-inline" id="arxiu-festa-result">' +
                    '<label>🎉 Festa detectada</label>' +
                    '<span class="arxiu-detected-value">' + festa.festa_nom + ' ' +
                    '<span class="arxiu-confidence-badge arxiu-confidence-' + festa.confidence + '">' + festa.confidence + '</span>' +
                    '</span></div>';
                
                if (!$('#arxiu-festa-result').length) {
                    $results.append(festaHtml);
                } else {
                    $('#arxiu-festa-result').replaceWith(festaHtml);
                }
            }
            
            // Data estimate
            if (data.date_estimate && data.date_estimate.year) {
                this.applyDateEstimate(data.date_estimate);
            }
            
            $results.show();
            $('#arxiu-reanalyze-content').show();
            
            this.saveDescriptionData();
        },
        
        /**
         * Vincular botó per fer scroll al mapa
         */
        bindScrollMapButton: function() {
            $(document).off('click', '.arxiu-scroll-map-btn');
            $(document).on('click', '.arxiu-scroll-map-btn', function(e) {
                e.preventDefault();
                var $mapBox = $('#arxiu_ubicacio_map');
                if ($mapBox.length) {
                    $('html, body').animate({
                        scrollTop: $mapBox.offset().top - 50
                    }, 500);
                }
            });
        },
        
        /**
         * Vincular botons d'aplicar ubicació
         */
        bindApplyUbicacioButtons: function() {
            var self = this;
            
            // Desvincular events anteriors per evitar duplicats
            $(document).off('click', '.arxiu-apply-ubicacio-btn');
            
            // Vincular event
            $(document).on('click', '.arxiu-apply-ubicacio-btn', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var poiSlug = String($btn.data('poi') || '');
                var barriSlug = String($btn.data('barri') || '');
                
                console.log('═══════════════════════════════════════════════════');
                console.log('🗺️ APLICANT UBICACIÓ DES D\'ANÀLISI IA:');
                console.log('   POI slug:', poiSlug || '(cap)');
                console.log('   Barri slug:', barriSlug || '(cap)');
                
                var applied = false;
                
                // Intentar via API del mapa
                if (typeof window.ArxiuUbicacio !== 'undefined' && 
                    typeof window.ArxiuUbicacio.applySelection === 'function') {
                    
                    console.log('   → Usant API ArxiuUbicacio.applySelection()');
                    window.ArxiuUbicacio.applySelection(poiSlug, barriSlug);
                    applied = true;
                    
                } else {
                    console.log('   → ArxiuUbicacio no disponible, usant fallback');
                }
                
                // SEMPRE fer fallback als checkboxes per assegurar que es guarden
                self.applyUbicacioFallback(poiSlug, barriSlug);
                
                // Feedback visual
                $btn.addClass('button-disabled').html('✓ Aplicat!');
                setTimeout(function() {
                    $btn.removeClass('button-disabled').html('<span class="dashicons dashicons-location"></span> Aplicar al mapa');
                }, 2000);
                
                // Scroll al mapa
                var $mapBox = $('#arxiu_ubicacio_map');
                if ($mapBox.length) {
                    $('html, body').animate({
                        scrollTop: $mapBox.offset().top - 50
                    }, 500);
                }
                
                console.log('═══════════════════════════════════════════════════');
            });
        },
        
        /**
         * Fallback per aplicar ubicació si no hi ha API del mapa
         */
        applyUbicacioFallback: function(poiSlug, barriSlug) {
            // Marcar checkboxes de taxonomia
            if (barriSlug) {
                var $barriChk = $('.taxonomy-ubicacio input[value="' + barriSlug + '"]');
                if ($barriChk.length && !$barriChk.prop('checked')) {
                    $barriChk.click();
                    console.log('✓ Barri aplicat via checkbox:', barriSlug);
                }
            }
            
            if (poiSlug) {
                var $poiChk = $('.taxonomy-ubicacio input[value="' + poiSlug + '"]');
                if ($poiChk.length && !$poiChk.prop('checked')) {
                    $poiChk.click();
                    console.log('✓ POI aplicat via checkbox:', poiSlug);
                }
            }
        },
        
        saveDescriptionData: function() {
            var data = this.descriptionData;
            
            // Actualitzar amb contingut editat (extreure text de spans)
            var tags = [];
            $('#arxiu-visual-tags .arxiu-tag').each(function() {
                tags.push($(this).text().trim());
            });
            data.tags_val = tags.length > 0 ? tags : (data.tags_val || []);
            
            data.summary = $('#arxiu-description-text').text();
            
            $('#arxiu-description-data').val(JSON.stringify(data));
        },
        
        // ═══════════════════════════════════════════════════════════════════════
        // GÈNERES
        // ═══════════════════════════════════════════════════════════════════════
        
        bindGenreEvents: function() {
            var self = this;
            
            // Estils interactius
            $(document).on('change', '.arxiu-genre-btn input', function() {
                var $label = $(this).closest('.arxiu-genre-btn');
                if ($(this).is(':checked')) {
                    $label.addClass('selected');
                } else {
                    $label.removeClass('selected');
                }
            });
            
            // Classificar amb IA
            $(document).on('click', '#arxiu-classify-genre', function(e) {
                e.preventDefault();
                var imageId = $('#arxiu-image-id').val() || $('#php-saved-img-id').val();
                if (imageId) {
                    self.classifyGenre(imageId);
                } else {
                    self.updateLog('#arxiu-genre-log', 'warning', 'Selecciona una imatge primer');
                }
            });
        },
        
        classifyGenre: function(imageId) {
            var self = this;
            
            // Si ja tenim dades d'anàlisi amb gènere, no cal fer crida extra
            if (this.descriptionData && this.descriptionData.genre_term_id) {
                this.updateLog('#arxiu-genre-log', 'info', 'ℹ️ Gènere ja detectat en l\'anàlisi');
                
                // Aplicar gènere existent
                $('.arxiu-genre-btn').removeClass('selected').find('input').prop('checked', false);
                var $target = $('.arxiu-genre-btn input[value="' + this.descriptionData.genre_term_id + '"]');
                if ($target.length) {
                    $target.prop('checked', true).closest('.arxiu-genre-btn').addClass('selected');
                    this.updateLog('#arxiu-genre-log', 'success', '✓ ' + (this.descriptionData.genre_term_name || this.descriptionData.genre));
                }
                return;
            }
            
            this.updateLog('#arxiu-genre-log', 'info', '🤖 Classificant imatge...');
            $('#arxiu-classify-genre').prop('disabled', true);
            
            $.ajax({
                url: ArxiuConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'arxiu_classify_genre',
                    nonce: ArxiuConfig.nonce,
                    image_id: imageId
                },
                success: function(response) {
                    if (response.success) {
                        self.updateLog('#arxiu-genre-log', 'success', '✓ Classificat: ' + response.data.term_name);
                        
                        // Deseleccionar tots
                        $('.arxiu-genre-btn').removeClass('selected').find('input').prop('checked', false);
                        
                        // Seleccionar el correcte
                        var $target = $('.arxiu-genre-btn input[value="' + response.data.term_id + '"]');
                        if ($target.length) {
                            $target.prop('checked', true).closest('.arxiu-genre-btn').addClass('selected');
                        }
                    } else {
                        self.updateLog('#arxiu-genre-log', 'error', response.data);
                    }
                },
                error: function() {
                    self.updateLog('#arxiu-genre-log', 'error', 'Error de connexió');
                },
                complete: function() {
                    $('#arxiu-classify-genre').prop('disabled', false);
                }
            });
        },
        
        // ═══════════════════════════════════════════════════════════════════════
        // DATA (SLIDERS)
        // ═══════════════════════════════════════════════════════════════════════
        
        bindDateEvents: function() {
            var self = this;
            
            // Toggle mode
            $(document).on('change', 'input[name="data_mode"]', function() {
                self.toggleDateMode($(this).val());
            });
            
            // Detectar data
            $(document).on('click', '#arxiu-detect-date', function(e) {
                e.preventDefault();
                self.detectDate();
            });
        },
        
        initDateSliders: function() {
            var self = this;
            var currentYear = new Date().getFullYear();
            
            // Slider exacte
            var $sliderExact = document.getElementById('year-slider-exact');
            if ($sliderExact && typeof noUiSlider !== 'undefined') {
                var startExact = parseInt($('#year_exact').val()) || 1970;
                
                noUiSlider.create($sliderExact, {
                    start: [startExact],
                    connect: false,
                    step: 1,
                    tooltips: [true],
                    range: { 'min': 1850, 'max': currentYear },
                    format: {
                        to: function(v) { return Math.round(v); },
                        from: function(v) { return parseInt(v); }
                    },
                    pips: {
                        mode: 'steps',
                        density: 5,
                        filter: function(value) { return value % 10 === 0 ? 1 : 0; },
                        format: { to: function(v) { return v % 10 === 0 ? v : ''; }, from: function(v) { return v; } }
                    }
                });
                
                $sliderExact.noUiSlider.on('update', function(values) {
                    $('#year_exact').val(values[0]);
                });
                
                this.sliderExact = $sliderExact.noUiSlider;
            }
            
            // Slider rang
            var $sliderRange = document.getElementById('year-slider-range');
            if ($sliderRange && typeof noUiSlider !== 'undefined') {
                var startMin = parseInt($('#year_min').val()) || 1960;
                var startMax = parseInt($('#year_max').val()) || 1990;
                
                noUiSlider.create($sliderRange, {
                    start: [startMin, startMax],
                    connect: true,
                    step: 1,
                    tooltips: [true, true],
                    range: { 'min': 1850, 'max': currentYear },
                    format: {
                        to: function(v) { return Math.round(v); },
                        from: function(v) { return parseInt(v); }
                    },
                    pips: {
                        mode: 'steps',
                        density: 5,
                        filter: function(value) { return value % 10 === 0 ? 1 : 0; },
                        format: { to: function(v) { return v % 10 === 0 ? v : ''; }, from: function(v) { return v; } }
                    }
                });
                
                $sliderRange.noUiSlider.on('update', function(values) {
                    $('#year_min').val(values[0]);
                    $('#year_max').val(values[1]);
                    $('#year-slider-min').text(values[0]);
                    $('#year-slider-max').text(values[1]);
                });
                
                this.sliderRange = $sliderRange.noUiSlider;
            }
        },
        
        toggleDateMode: function(mode) {
            if (mode === 'exact') {
                $('#data-slider-exact-wrap').show();
                $('#data-slider-range-wrap').hide();
            } else {
                $('#data-slider-exact-wrap').hide();
                $('#data-slider-range-wrap').show();
            }
        },
        
        detectDate: function() {
            var self = this;
            var title = $('#title').val() || '';
            var filename = ArxiuConfig.existingFilename || '';
            var combined = title + ' ' + filename;
            
            if (!combined.trim()) {
                this.updateLog('#arxiu-date-log', 'warning', 'No hi ha títol per analitzar');
                return;
            }
            
            this.updateLog('#arxiu-date-log', 'info', '🔍 Detectant dates...');
            
            // Mostrar que estem analitzant
            $('#step-title').addClass('active');
            $('#step-title-status').text('Analitzant...');
            
            // Nivell 1: Títol
            $.ajax({
                url: ArxiuConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'arxiu_analyze_title',
                    nonce: ArxiuConfig.nonce,
                    title: combined
                },
                success: function(response) {
                    if (response.success && response.data.year) {
                        $('#step-title').removeClass('active').addClass('success');
                        $('#step-title-status').text('✓ ' + response.data.year);
                        self.updateLog('#arxiu-date-log', 'success', '✓ Data detectada: ' + response.data.year);
                        self.applyDateEstimate(response.data);
                    } else {
                        $('#step-title').removeClass('active').addClass('failed');
                        $('#step-title-status').text('—');
                        self.updateLog('#arxiu-date-log', 'warning', 'Cap data trobada al títol');
                    }
                }
            });
        },
        
        /**
         * Detecció automàtica de data (s'executa al pujar imatge)
         */
        autoDetectDate: function() {
            var self = this;
            var title = $('#title').val() || '';
            var filename = ArxiuConfig.existingFilename || '';
            var combined = title + ' ' + filename;
            
            if (!combined.trim()) {
                console.log('⏭️ No hi ha títol per detectar data');
                return;
            }
            
            console.log('🔍 Detectant data automàticament...');
            
            $.ajax({
                url: ArxiuConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'arxiu_analyze_title',
                    nonce: ArxiuConfig.nonce,
                    title: combined
                },
                success: function(response) {
                    if (response.success && response.data.year) {
                        console.log('✅ Data detectada:', response.data.year, '-', response.data.reason || 'sense raó');
                        self.applyDateEstimate(response.data);
                        
                        // Mostrar feedback visual
                        $('#step-title').addClass('success');
                        $('#step-title-status').text('✓ ' + response.data.year);
                    }
                }
            });
        },
        
        applyDateEstimate: function(estimate) {
            if (!estimate.year) return;
            
            var year = parseInt(estimate.year);
            var yearEnd = estimate.year_end ? parseInt(estimate.year_end) : null;
            
            if (yearEnd && yearEnd !== year) {
                // Rang
                $('input[name="data_mode"][value="rango"]').prop('checked', true);
                this.toggleDateMode('rango');
                
                if (this.sliderRange) {
                    this.sliderRange.set([year, yearEnd]);
                }
            } else {
                // Exacte
                $('input[name="data_mode"][value="exact"]').prop('checked', true);
                this.toggleDateMode('exact');
                
                if (this.sliderExact) {
                    this.sliderExact.set([year]);
                }
            }
            
            // Mes i dia
            if (estimate.month) {
                $('select[name="data_mes"]').val(estimate.month);
            }
            if (estimate.day) {
                $('input[name="data_dia"]').val(estimate.day);
            }
            
            // Font/Justificació - Mostrar al camp i al panell de resultat
            var sourceText = '';
            if (estimate.source) {
                sourceText = estimate.source;
                if (estimate.reason) {
                    sourceText += ' — ' + estimate.reason;
                }
            } else if (estimate.reason) {
                sourceText = estimate.reason;
            }
            
            if (sourceText) {
                $('#arxiu_date_source').val(sourceText);
                
                // Mostrar resultat visible
                var $result = $('#arxiu-detection-result');
                var yearText = yearEnd && yearEnd !== year ? year + ' - ' + yearEnd : year;
                var resultHtml = '<strong>📅 Any detectat: ' + yearText + '</strong>';
                if (estimate.reason) {
                    resultHtml += '<br><em>💡 ' + estimate.reason + '</em>';
                }
                if (estimate.confidence) {
                    resultHtml += ' <span class="arxiu-confidence-badge arxiu-confidence-' + estimate.confidence + '">' + estimate.confidence + '</span>';
                }
                $result.html(resultHtml).addClass('success').show();
            }
        },
        
        // ═══════════════════════════════════════════════════════════════════════
        // UBICACIÓ (MAPBOX)
        // ═══════════════════════════════════════════════════════════════════════
        
        bindLocationEvents: function() {
            var self = this;
            
            $(document).on('click', '#arxiu-clear-location', function(e) {
                e.preventDefault();
                self.clearLocation();
            });
            
            // Event per marcar ubicació al mapa des de l'anàlisi IA
            $(document).on('click', '.arxiu-mark-map-btn', function(e) {
                e.preventDefault();
                var slug = $(this).data('slug');
                
                if (slug && typeof window.ArxiuUbicacio !== 'undefined') {
                    var success = window.ArxiuUbicacio.selectFromIA(slug);
                    if (success) {
                        $(this).addClass('button-primary').html('<span class="dashicons dashicons-yes"></span> Marcat');
                        
                        // Scroll suau al mapa
                        $('html, body').animate({
                            scrollTop: $('#arxiu-map-container').offset().top - 50
                        }, 500);
                    } else {
                        $(this).addClass('button-disabled').text('No trobat');
                    }
                }
            });
        },
        
        initMapbox: function() {
            var self = this;
            var config = ArxiuConfig.mapbox;
            
            if (!config || !config.token || typeof mapboxgl === 'undefined') {
                return;
            }
            
            mapboxgl.accessToken = config.token;
            
            this.map = new mapboxgl.Map({
                container: 'arxiu-map',
                style: config.style,
                center: config.center,
                zoom: 13.5,
                pitchWithRotate: false,
                dragRotate: false
            });
            
            // Inicialitzar selecció
            if (config.selected && config.selected.length) {
                config.selected.forEach(function(slug) {
                    self.selectedSlugs.add(slug);
                });
            }
            
            this.map.on('load', function() {
                self.initMapSources();
                self.initMapLayers();
                self.initMapEvents();
                
                setTimeout(function() { self.map.resize(); }, 100);
            });
        },
        
        initMapSources: function() {
            var config = ArxiuConfig.mapbox;
            
            this.map.addSource('src-barris', {
                type: 'vector',
                url: config.sources.barris,
                promoteId: 'barri_slug'
            });
            
            this.map.addSource('src-pois', {
                type: 'vector',
                url: config.sources.pois,
                promoteId: 'poi_slug'
            });
        },
        
        initMapLayers: function() {
            var config = ArxiuConfig.mapbox;
            
            // Barris (polígons)
            this.map.addLayer({
                id: 'ly-barri-fill',
                type: 'fill',
                source: 'src-barris',
                'source-layer': config.layers.barri,
                paint: {
                    'fill-color': [
                        'case',
                        ['boolean', ['feature-state', 'selected'], false],
                        '#ff6600',
                        '#ffb0e0'
                    ],
                    'fill-opacity': 0.4
                }
            });
            
            this.map.addLayer({
                id: 'ly-barri-line',
                type: 'line',
                source: 'src-barris',
                'source-layer': config.layers.barri,
                paint: { 'line-color': '#999', 'line-width': 1 }
            });
            
            // POIs (cercles)
            this.map.addLayer({
                id: 'ly-poi-circle',
                type: 'circle',
                source: 'src-pois',
                'source-layer': config.layers.poi,
                paint: {
                    'circle-radius': 10,
                    'circle-stroke-width': 3,
                    'circle-stroke-color': '#fff',
                    'circle-color': [
                        'case',
                        ['boolean', ['feature-state', 'selected'], false],
                        '#ff0000',
                        '#0088ff'
                    ]
                }
            });
            
            // Etiquetes POI
            this.map.addLayer({
                id: 'ly-poi-label',
                type: 'symbol',
                source: 'src-pois',
                'source-layer': config.layers.poi,
                filter: ['has', 'name'],
                layout: {
                    'text-field': ['get', 'name'],
                    'text-size': 13,
                    'text-offset': [0, 1.5],
                    'text-anchor': 'top',
                    'text-font': ['Open Sans Bold', 'Arial Unicode MS Bold']
                },
                paint: {
                    'text-halo-color': '#fff',
                    'text-halo-width': 2,
                    'text-color': '#333'
                }
            });
        },
        
        initMapEvents: function() {
            var self = this;
            var config = ArxiuConfig.mapbox;
            
            // Carregar features
            this.map.on('sourcedata', function(e) {
                if (e.isSourceLoaded) {
                    self.loadMapFeatures();
                }
            });
            
            // Click POI
            this.map.on('click', ['ly-poi-circle', 'ly-poi-label'], function(e) {
                if (e.features && e.features.length) {
                    e.originalEvent.stopPropagation();
                    self.selectPoi(e.features[0], e.lngLat);
                }
            });
            
            // Click Barri
            this.map.on('click', 'ly-barri-fill', function(e) {
                var poisAtPoint = self.map.queryRenderedFeatures(e.point, { layers: ['ly-poi-circle', 'ly-poi-label'] });
                if (poisAtPoint.length === 0 && e.features && e.features.length) {
                    self.selectBarri(e.features[0], e.lngLat);
                }
            });
            
            // Cursors
            ['ly-poi-circle', 'ly-poi-label', 'ly-barri-fill'].forEach(function(layer) {
                self.map.on('mouseenter', layer, function() {
                    self.map.getCanvas().style.cursor = 'pointer';
                });
                self.map.on('mouseleave', layer, function() {
                    self.map.getCanvas().style.cursor = '';
                });
            });
        },
        
        loadMapFeatures: function() {
            var self = this;
            var config = ArxiuConfig.mapbox;
            
            // Barris
            var barris = this.map.querySourceFeatures('src-barris', { sourceLayer: config.layers.barri });
            barris.forEach(function(f) {
                var slug = f.properties.barri_slug;
                if (slug) {
                    self.allFeaturesMap.set(slug, {
                        type: 'barri',
                        name: f.properties.name || slug,
                        source: 'src-barris',
                        layer: config.layers.barri
                    });
                    
                    if (self.selectedSlugs.has(slug)) {
                        self.setFeatureState(slug, true);
                    }
                }
            });
            
            // POIs
            var pois = this.map.querySourceFeatures('src-pois', { sourceLayer: config.layers.poi });
            pois.forEach(function(f) {
                var slug = f.properties.poi_slug;
                if (slug) {
                    self.allFeaturesMap.set(slug, {
                        type: 'poi',
                        name: f.properties.name || slug,
                        source: 'src-pois',
                        layer: config.layers.poi,
                        parentBarri: f.properties.parent_barri_slug || null
                    });
                    
                    if (self.selectedSlugs.has(slug)) {
                        self.setFeatureState(slug, true);
                    }
                }
            });
            
            this.updateLocationUI();
        },
        
        setFeatureState: function(slug, isSelected) {
            var feature = this.allFeaturesMap.get(slug);
            if (!feature) return;
            
            this.map.setFeatureState(
                { source: feature.source, sourceLayer: feature.layer, id: slug },
                { selected: isSelected }
            );
        },
        
        selectPoi: function(feature, lngLat) {
            var slug = String(feature.properties.poi_slug);
            var name = feature.properties.name || slug;
            var parentSlug = feature.properties.parent_barri_slug;
            
            // Afegir POI
            if (!this.selectedSlugs.has(slug)) {
                this.selectedSlugs.add(slug);
                this.setFeatureState(slug, true);
                this.syncCheckbox(slug, true);
            }
            
            // Afegir barri pare
            if (parentSlug && !this.selectedSlugs.has(parentSlug)) {
                this.selectedSlugs.add(parentSlug);
                this.setFeatureState(parentSlug, true);
                this.syncCheckbox(parentSlug, true);
            }
            
            $('#arxiu-lat').val(lngLat.lat.toFixed(5));
            $('#arxiu-lng').val(lngLat.lng.toFixed(5));
            this.updateLocationUI();
        },
        
        selectBarri: function(feature, lngLat) {
            var slug = String(feature.properties.barri_slug);
            
            if (!this.selectedSlugs.has(slug)) {
                this.selectedSlugs.add(slug);
                this.setFeatureState(slug, true);
                this.syncCheckbox(slug, true);
            }
            
            $('#arxiu-lat').val(lngLat.lat.toFixed(5));
            $('#arxiu-lng').val(lngLat.lng.toFixed(5));
            this.updateLocationUI();
        },
        
        syncCheckbox: function(slug, checked) {
            var $chk = $('.taxonomy-ubicacio input[value="' + slug + '"]');
            if ($chk.length && $chk.is(':checked') !== checked) {
                $chk.click();
            }
        },
        
        updateLocationUI: function() {
            var names = [];
            var self = this;
            
            this.selectedSlugs.forEach(function(slug) {
                var feature = self.allFeaturesMap.get(slug);
                if (feature) names.push(feature.name);
            });
            
            $('#arxiu-area').val(names.join(', '));
        },
        
        clearLocation: function() {
            var self = this;
            
            this.selectedSlugs.forEach(function(slug) {
                self.setFeatureState(slug, false);
                self.syncCheckbox(slug, false);
            });
            
            this.selectedSlugs.clear();
            $('#arxiu-area').val('');
            $('#arxiu-lat').val('');
            $('#arxiu-lng').val('');
        },
        
        // ═══════════════════════════════════════════════════════════════════════
        // UTILITATS
        // ═══════════════════════════════════════════════════════════════════════
        
        updateStatus: function(selector, type, message) {
            var $el = $(selector);
            $el.removeClass('loading ready error warning')
               .addClass(type)
               .html(type === 'loading' ? '<span class="spinner is-active"></span> ' + message : message);
        },
        
        updateLog: function(selector, type, message) {
            var icons = { info: 'ℹ️', success: '✓', warning: '⚠️', error: '❌' };
            var colors = { info: '#2271b1', success: '#00a32a', warning: '#dba617', error: '#d63638' };
            
            $(selector).html('<span style="color:' + colors[type] + ';">' + message + '</span>');
        }
    };

    // Inicialitzar quan el DOM estigui llest
    $(document).ready(function() {
        ArxiuAdmin.init();
    });

})(jQuery);
