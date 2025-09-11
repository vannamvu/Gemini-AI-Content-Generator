/**
 * Gemini AI Content Generator - Enhanced Progress Tracking
 */
(function($) {
    'use strict';

    // Post status constants
    const POST_STATUS = {
        QUEUED: 'queued',
        PROCESSING: 'processing', 
        CONTENT_GENERATED: 'content_generated',
        IMAGES_PROCESSING: 'images_processing',
        SEO_OPTIMIZATION: 'seo_optimization',
        PUBLISHING: 'publishing',
        COMPLETED: 'completed',
        ERROR: 'error'
    };

    // Status display config
    const STATUS_CONFIG = {
        [POST_STATUS.QUEUED]: {
            icon: '🔄',
            text: 'Đang chờ xử lý',
            color: '#6c757d',
            progress: 0
        },
        [POST_STATUS.PROCESSING]: {
            icon: '⚡',
            text: 'Bắt đầu xử lý',
            color: '#007bff',
            progress: 10
        },
        [POST_STATUS.CONTENT_GENERATED]: {
            icon: '📝',
            text: 'Đã tạo nội dung',
            color: '#28a745',
            progress: 40
        },
        [POST_STATUS.IMAGES_PROCESSING]: {
            icon: '🖼️',
            text: 'Đang xử lý hình ảnh',
            color: '#ffc107',
            progress: 60
        },
        [POST_STATUS.SEO_OPTIMIZATION]: {
            icon: '🎯',
            text: 'Tối ưu SEO',
            color: '#17a2b8',
            progress: 80
        },
        [POST_STATUS.PUBLISHING]: {
            icon: '📤',
            text: 'Đang đăng bài',
            color: '#fd7e14',
            progress: 90
        },
        [POST_STATUS.COMPLETED]: {
            icon: '✅',
            text: 'Hoàn thành',
            color: '#28a745',
            progress: 100
        },
        [POST_STATUS.ERROR]: {
            icon: '❌',
            text: 'Có lỗi xảy ra',
            color: '#dc3545',
            progress: 0
        }
    };

    window.GACG = {
        posts: [],
        currentIndex: 0,
        startTime: null,
        isProcessing: false,
        
        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initFormValidation();
        },

        bindEvents: function() {
            $(document).on('click', '#gacg-generate-content', this.generateContent);
            $(document).on('submit', '#gacg-bulk-content-form', this.handleBulkCreation);
            $(document).on('change', '#gacg-publish-option', this.toggleScheduleOptions);
            $(document).on('input', '#gacg-title-list', this.updateTitleCount);
            $(document).on('click', '.gacg-cancel-scheduled', this.cancelScheduledPost);
            $(document).on('click', '#test-api-connection', this.testApiConnection);
            $(document).on('click', '.gacg-retry-post', this.retryPost);
            $(document).on('click', '#gacg-pause-processing', this.pauseProcessing);
            $(document).on('click', '#gacg-resume-processing', this.resumeProcessing);
        },

        initTabs: function() {
            $('.gacg-tab').on('click', function() {
                var target = $(this).data('tab');
                $('.gacg-tab').removeClass('active');
                $('.gacg-tab-content').removeClass('active');
                $(this).addClass('active');
                $('#' + target).addClass('active');
            });
        },

        initFormValidation: function() {
            $('input[required], select[required], textarea[required]').on('blur', function() {
                GACG.validateField($(this));
            });
        },

        validateField: function($field) {
            var isValid = true;
            var value = $field.val().trim();
            
            $field.removeClass('error');
            $field.next('.error-message').remove();
            
            if ($field.prop('required') && !value) {
                isValid = false;
                $field.addClass('error');
                $field.after('<span class="error-message" style="color: #dc3545; font-size: 0.9em;">Trường này là bắt buộc</span>');
            }
            
            return isValid;
        },

        handleBulkCreation: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var formData = $form.serialize();
            
            if (!GACG.validateBulkForm($form)) {
                return;
            }
            
            var titles = $('#gacg-title-list').val().split('\n').filter(function(title) {
                return title.trim() !== '';
            });
            
            GACG.initializeBulkCreation(titles, formData);
        },

        validateBulkForm: function($form) {
            var isValid = true;
            
            $form.find('input[required], select[required], textarea[required]').each(function() {
                if (!GACG.validateField($(this))) {
                    isValid = false;
                }
            });
            
            var titles = $('#gacg-title-list').val().split('\n').filter(function(title) {
                return title.trim() !== '';
            });
            
            if (titles.length === 0) {
                GACG.showAlert('Vui lòng nhập ít nhất 1 tiêu đề bài viết', 'error');
                $('#gacg-title-list').addClass('error');
                isValid = false;
            }
            
            if ($('#gacg-publish-option').val() === 'schedule') {
                var startDate = $('#gacg-start-date').val();
                if (!startDate) {
                    GACG.showAlert('Vui lòng chọn ngày bắt đầu đăng', 'error');
                    $('#gacg-start-date').addClass('error');
                    isValid = false;
                } else {
                    var selectedDate = new Date(startDate);
                    var now = new Date();
                    if (selectedDate <= now) {
                        GACG.showAlert('Ngày bắt đầu phải sau thời điểm hiện tại', 'error');
                        $('#gacg-start-date').addClass('error');
                        isValid = false;
                    }
                }
            }
            
            return isValid;
        },

        initializeBulkCreation: function(titles, formData) {
            // Initialize posts array
            GACG.posts = titles.map(function(title, index) {
                return {
                    id: index,
                    title: title.trim(),
                    status: POST_STATUS.QUEUED,
                    error: null,
                    startTime: null,
                    endTime: null,
                    postId: null
                };
            });

            GACG.currentIndex = 0;
            GACG.startTime = Date.now();
            GACG.isProcessing = true;
            
            // Show progress container
            GACG.showProgressContainer();
            
            // Disable form
            $('#gacg-bulk-content-form input, #gacg-bulk-content-form select, #gacg-bulk-content-form textarea').prop('disabled', true);
            
            // Start processing
            GACG.processNext();
        },

        showProgressContainer: function() {
            var $container = $('#gacg-progress-container');
            
            if ($container.length === 0) {
                $container = $(`
                    <div id="gacg-progress-container" class="gacg-card" style="margin-top: 20px;">
                        <h3>📊 Tiến trình tạo bài viết</h3>
                        
                        <!-- Overall Progress -->
                        <div class="gacg-overall-progress">
                            <div class="gacg-progress-stats">
                                <span id="progress-completed">0</span>/<span id="progress-total">0</span> bài hoàn thành
                                <span id="progress-percentage">(0%)</span>
                                <span id="progress-time-estimate" style="margin-left: 20px; color: #6c757d;"></span>
                            </div>
                            <div class="gacg-progress">
                                <div class="gacg-progress-bar" style="width: 0%"></div>
                            </div>
                        </div>

                        <!-- Control Buttons -->
                        <div class="gacg-progress-controls" style="margin: 15px 0;">
                            <button type="button" id="gacg-pause-processing" class="gacg-btn gacg-btn-secondary">⏸️ Tạm dừng</button>
                            <button type="button" id="gacg-resume-processing" class="gacg-btn gacg-btn-secondary" style="display: none;">▶️ Tiếp tục</button>
                            <button type="button" id="gacg-stop-processing" class="gacg-btn gacg-btn-danger">⏹️ Dừng hẳn</button>
                        </div>

                        <!-- Individual Posts Progress -->
                        <div id="posts-progress-list" class="posts-progress-list"></div>
                        
                        <!-- Live Log -->
                        <div class="gacg-live-log">
                            <h4>📝 Log trực tiếp</h4>
                            <div id="live-log" class="live-log-content"></div>
                        </div>
                    </div>
                `);
                
                $('#gacg-bulk-content-form').after($container);
            }
            
            $container.show();
            
            // Initialize progress display
            $('#progress-total').text(GACG.posts.length);
            GACG.updateProgressDisplay();
            GACG.renderPostsList();
        },

        renderPostsList: function() {
            var $list = $('#posts-progress-list');
            $list.empty();
            
            GACG.posts.forEach(function(post) {
                var status = STATUS_CONFIG[post.status];
                var $item = $(`
                    <div class="post-progress-item" data-post-id="${post.id}">
                        <div class="post-info">
                            <span class="post-status-icon">${status.icon}</span>
                            <span class="post-title">${post.title}</span>
                            <span class="post-status-text" style="color: ${status.color};">${status.text}</span>
                        </div>
                        <div class="post-actions">
                            ${post.status === POST_STATUS.ERROR ? 
                                `<button class="gacg-btn gacg-btn-sm gacg-retry-post" data-post-id="${post.id}">🔄 Thử lại</button>` : 
                                ''
                            }
                            ${post.status === POST_STATUS.COMPLETED && post.postId ? 
                                `<a href="/wp-admin/post.php?post=${post.postId}&action=edit" class="gacg-btn gacg-btn-sm" target="_blank">✏️ Chỉnh sửa</a>` : 
                                ''
                            }
                        </div>
                        <div class="post-progress">
                            <div class="post-progress-bar" style="width: ${status.progress}%; background-color: ${status.color};"></div>
                        </div>
                        ${post.error ? `<div class="post-error">${post.error}</div>` : ''}
                    </div>
                `);
                
                $list.append($item);
            });
        },

        updateProgressDisplay: function() {
            var completed = GACG.posts.filter(p => p.status === POST_STATUS.COMPLETED).length;
            var total = GACG.posts.length;
            var percentage = total > 0 ? Math.round((completed / total) * 100) : 0;
            
            $('#progress-completed').text(completed);
            $('#progress-percentage').text('(' + percentage + '%)');
            $('.gacg-progress-bar').css('width', percentage + '%');
            
            // Time estimate
            if (completed > 0 && GACG.startTime) {
                var elapsed = Date.now() - GACG.startTime;
                var avgTimePerPost = elapsed / completed;
                var remaining = total - completed;
                var estimatedTime = (remaining * avgTimePerPost) / 1000 / 60; // minutes
                
                if (estimatedTime > 60) {
                    var hours = Math.floor(estimatedTime / 60);
                    var minutes = Math.round(estimatedTime % 60);
                    $('#progress-time-estimate').text(`Ước tính: ${hours}h${minutes}m`);
                } else {
                    $('#progress-time-estimate').text(`Ước tính: ${Math.round(estimatedTime)}m`);
                }
            }
        },

        processNext: function() {
            if (!GACG.isProcessing) {
                return;
            }

            // Find next queued post
            var nextPost = GACG.posts.find(p => p.status === POST_STATUS.QUEUED);
            
            if (!nextPost) {
                // All posts processed
                GACG.completeBulkCreation();
                return;
            }

            GACG.processSinglePost(nextPost);
        },

        processSinglePost: function(post) {
            post.startTime = Date.now();
            
            GACG.updatePostStatus(post.id, POST_STATUS.PROCESSING);
            GACG.addLog(`Bắt đầu xử lý: "${post.title}"`);

            // Step 1: Generate Content
            GACG.generateContentForPost(post);
        },

        generateContentForPost: function(post) {
            $.post(gacg_ajax.ajax_url, {
                action: 'gacg_generate_content',
                nonce: gacg_ajax.nonce,
                title: post.title
            }, function(response) {
                if (response.success) {
                    post.content = response.data.content;
                    GACG.updatePostStatus(post.id, POST_STATUS.CONTENT_GENERATED);
                    GACG.addLog(`✅ Đã tạo nội dung cho: "${post.title}"`);
                    
                    // Step 2: Process Images (if enabled)
                    if ($('#gacg-image-count').val() > 0) {
                        GACG.processImagesForPost(post);
                    } else {
                        GACG.optimizeSEOForPost(post);
                    }
                } else {
                    GACG.handlePostError(post, response.data || 'Lỗi tạo nội dung');
                }
            }).fail(function() {
                GACG.handlePostError(post, 'Lỗi kết nối khi tạo nội dung');
            });
        },

        processImagesForPost: function(post) {
            GACG.updatePostStatus(post.id, POST_STATUS.IMAGES_PROCESSING);
            GACG.addLog(`🖼️ Đang xử lý hình ảnh cho: "${post.title}"`);
            
            // Simulate image processing (replace with actual implementation)
            setTimeout(function() {
                GACG.addLog(`✅ Đã xử lý hình ảnh cho: "${post.title}"`);
                GACG.optimizeSEOForPost(post);
            }, 2000);
        },

        optimizeSEOForPost: function(post) {
            GACG.updatePostStatus(post.id, POST_STATUS.SEO_OPTIMIZATION);
            GACG.addLog(`🎯 Đang tối ưu SEO cho: "${post.title}"`);
            
            // Simulate SEO optimization (replace with actual implementation)
            setTimeout(function() {
                GACG.addLog(`✅ Đã tối ưu SEO cho: "${post.title}"`);
                GACG.publishPost(post);
            }, 1000);
        },

        publishPost: function(post) {
            GACG.updatePostStatus(post.id, POST_STATUS.PUBLISHING);
            GACG.addLog(`📤 Đang đăng bài: "${post.title}"`);
            
            var publishOption = $('#gacg-publish-option').val();
            var categoryId = $('#gacg-category').val();
            
            $.post(gacg_ajax.ajax_url, {
                action: 'gacg_publish_post',
                nonce: gacg_ajax.nonce,
                title: post.title,
                content: post.content,
                category: categoryId,
                publish_option: publishOption
            }, function(response) {
                if (response.success) {
                    post.postId = response.data.post_id;
                    post.endTime = Date.now();
                    GACG.updatePostStatus(post.id, POST_STATUS.COMPLETED);
                    GACG.addLog(`🎉 Hoàn thành: "${post.title}"`);
                    
                    // Process next post after a short delay
                    setTimeout(function() {
                        if (GACG.isProcessing) {
                            GACG.processNext();
                        }
                    }, 1000);
                } else {
                    GACG.handlePostError(post, response.data || 'Lỗi đăng bài');
                }
            }).fail(function() {
                GACG.handlePostError(post, 'Lỗi kết nối khi đăng bài');
            });
        },

        updatePostStatus: function(postId, status) {
            var post = GACG.posts.find(p => p.id === postId);
            if (post) {
                post.status = status;
                
                // Update UI
                var $item = $(`.post-progress-item[data-post-id="${postId}"]`);
                var statusConfig = STATUS_CONFIG[status];
                
                $item.find('.post-status-icon').text(statusConfig.icon);
                $item.find('.post-status-text').text(statusConfig.text).css('color', statusConfig.color);
                $item.find('.post-progress-bar').css({
                    'width': statusConfig.progress + '%',
                    'background-color': statusConfig.color
                });
                
                // Update retry button
                if (status === POST_STATUS.ERROR) {
                    if ($item.find('.gacg-retry-post').length === 0) {
                        $item.find('.post-actions').append(`<button class="gacg-btn gacg-btn-sm gacg-retry-post" data-post-id="${postId}">🔄 Thử lại</button>`);
                    }
                } else {
                    $item.find('.gacg-retry-post').remove();
                }
                
                // Add edit link for completed posts
                if (status === POST_STATUS.COMPLETED && post.postId) {
                    if ($item.find('a[href*="post.php"]').length === 0) {
                        $item.find('.post-actions').append(`<a href="/wp-admin/post.php?post=${post.postId}&action=edit" class="gacg-btn gacg-btn-sm" target="_blank">✏️ Chỉnh sửa</a>`);
                    }
                }
                
                GACG.updateProgressDisplay();
            }
        },

        handlePostError: function(post, errorMessage) {
            post.error = errorMessage;
            post.endTime = Date.now();
            GACG.updatePostStatus(post.id, POST_STATUS.ERROR);
            GACG.addLog(`❌ Lỗi "${post.title}": ${errorMessage}`, 'error');
            
            // Continue with next post
            setTimeout(function() {
                if (GACG.isProcessing) {
                    GACG.processNext();
                }
            }, 1000);
        },

        retryPost: function(e) {
            e.preventDefault();
            var postId = parseInt($(this).data('post-id'));
            var post = GACG.posts.find(p => p.id === postId);
            
            if (post) {
                post.status = POST_STATUS.QUEUED;
                post.error = null;
                GACG.updatePostStatus(postId, POST_STATUS.QUEUED);
                GACG.addLog(`🔄 Thử lại: "${post.title}"`);
                
                if (!GACG.isProcessing) {
                    GACG.isProcessing = true;
                    GACG.processNext();
                }
            }
        },

        pauseProcessing: function(e) {
            e.preventDefault();
            GACG.isProcessing = false;
            $('#gacg-pause-processing').hide();
            $('#gacg-resume-processing').show();
            GACG.addLog('⏸️ Đã tạm dừng xử lý', 'info');
        },

        resumeProcessing: function(e) {
            e.preventDefault();
            GACG.isProcessing = true;
            $('#gacg-resume-processing').hide();
            $('#gacg-pause-processing').show();
            GACG.addLog('▶️ Tiếp tục xử lý', 'info');
            GACG.processNext();
        },

        completeBulkCreation: function() {
            GACG.isProcessing = false;
            $('#gacg-bulk-content-form input, #gacg-bulk-content-form select, #gacg-bulk-content-form textarea').prop('disabled', false);
            
            var completed = GACG.posts.filter(p => p.status === POST_STATUS.COMPLETED).length;
            var errors = GACG.posts.filter(p => p.status === POST_STATUS.ERROR).length;
            var total = GACG.posts.length;
            
            var totalTime = (Date.now() - GACG.startTime) / 1000 / 60; // minutes
            
            GACG.addLog(`🎉 Hoàn thành tất cả! ${completed}/${total} thành công (${Math.round(totalTime)}m)`, 'success');
            
            // Hide control buttons
            $('.gacg-progress-controls').hide();
            
            // Show summary
            var summaryHtml = `
                <div class="gacg-summary" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px;">
                    <h4>📊 Tổng kết</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                        <div><strong>Tổng bài:</strong> ${total}</div>
                        <div><strong>Thành công:</strong> <span style="color: #28a745;">${completed}</span></div>
                        <div><strong>Lỗi:</strong> <span style="color: #dc3545;">${errors}</span></div>
                        <div><strong>Thời gian:</strong> ${Math.round(totalTime)}m</div>
                        <div><strong>Tỷ lệ thành công:</strong> ${Math.round((completed/total)*100)}%</div>
                    </div>
                </div>
            `;
            
            $('#gacg-progress-container').append(summaryHtml);
            
            if (errors === 0) {
                GACG.showAlert('🎉 Tất cả bài viết đã được tạo thành công!', 'success');
            } else {
                GACG.showAlert(`⚠️ Hoàn thành với ${completed} thành công và ${errors} lỗi`, 'warning');
            }
        },

        addLog: function(message, type = 'info') {
            var $log = $('#live-log');
            var timestamp = new Date().toLocaleTimeString('vi-VN');
            var color = type === 'error' ? '#dc3545' : type === 'success' ? '#28a745' : '#6c757d';
            
            var $entry = $(`
                <div class="log-entry" style="margin-bottom: 5px; color: ${color};">
                    <span class="log-time" style="color: #6c757d;">[${timestamp}]</span>
                    <span class="log-message">${message}</span>
                </div>
            `);
            
            $log.append($entry);
            $log.scrollTop($log[0].scrollHeight);
            
            // Keep only last 50 entries
            if ($log.children().length > 50) {
                $log.children().first().remove();
            }
        },

        // Other existing methods remain the same...
        toggleScheduleOptions: function() {
            var $scheduleOptions = $('#schedule-options');
            if ($(this).val() === 'schedule') {
                $scheduleOptions.slideDown();
            } else {
                $scheduleOptions.slideUp();
            }
        },

        updateTitleCount: function() {
            var titles = $(this).val().split('\n').filter(function(title) {
                return title.trim() !== '';
            });
            
            $('#title-count').text(titles.length + ' tiêu đề');
            
            var estimatedMinutes = titles.length * 2;
            var timeText = '';
            
            if (estimatedMinutes < 60) {
                timeText = ' (ước tính: ~' + estimatedMinutes + ' phút)';
            } else {
                var hours = Math.floor(estimatedMinutes / 60);
                var minutes = estimatedMinutes % 60;
                timeText = ' (ước tính: ~' + hours + 'h' + (minutes > 0 ? ' ' + minutes + 'm' : '') + ')';
            }
            
            $('#title-count').append('<span style="color: #6c757d;">' + timeText + '</span>');
        },

        generateContent: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var title = $('#gacg-title').val().trim();
            
            if (!title) {
                GACG.showAlert('Vui lòng nhập tiêu đề bài viết', 'error');
                $('#gacg-title').focus();
                return;
            }
            
            GACG.setLoading($button, true);
            
            $.post(gacg_ajax.ajax_url, {
                action: 'gacg_generate_content',
                nonce: gacg_ajax.nonce,
                title: title
            }, function(response) {
                GACG.setLoading($button, false);
                
                if (response.success) {
                    $('#gacg-content').val(response.data.content);
                    GACG.showAlert('✨ Nội dung đã được tạo thành công!', 'success');
                    
                    $('html, body').animate({
                        scrollTop: $('#gacg-content').offset().top - 100
                    }, 500);
                } else {
                    GACG.showAlert('❌ Lỗi: ' + (response.data || 'Không thể tạo nội dung'), 'error');
                }
            }).fail(function() {
                GACG.setLoading($button, false);
                GACG.showAlert('❌ Lỗi kết nối. Vui lòng thử lại', 'error');
            });
        },

        cancelScheduledPost: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var scheduledId = $button.data('id');
            var $row = $button.closest('tr');
            
            if (!confirm('Bạn có chắc muốn hủy bài viết này?')) {
                return;
            }
            
            GACG.setLoading($button, true);
            
            $.post(gacg_ajax.ajax_url, {
                action: 'gacg_cancel_scheduled_post',
                nonce: gacg_ajax.nonce,
                scheduled_id: scheduledId
            }, function(response) {
                GACG.setLoading($button, false);
                
                if (response.success) {
                    $row.find('.gacg-badge').replaceWith('<span class="gacg-badge gacg-badge-secondary">Đã hủy</span>');
                    $button.remove();
                    GACG.showAlert('✅ Đã hủy bài viết thành công', 'success');
                } else {
                    GACG.showAlert('❌ Lỗi: ' + (response.data || 'Không thể hủy bài viết'), 'error');
                }
            }).fail(function() {
                GACG.setLoading($button, false);
                GACG.showAlert('❌ Lỗi kết nối', 'error');
            });
        },

        testApiConnection: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $result = $('#api-test-result');
            var apiKey = $('#gemini_api_key').val().trim();
            
            if (!apiKey) {
                $result.html('<div class="gacg-alert gacg-alert-error">❌ Vui lòng nhập API key trước</div>');
                $('#gemini_api_key').focus();
                return;
            }
            
            GACG.setLoading($button, true);
            $result.empty();
            
            $.post(gacg_ajax.ajax_url, {
                action: 'gacg_test_api_connection',
                nonce: gacg_ajax.nonce,
                api_key: apiKey
            }, function(response) {
                GACG.setLoading($button, false);
                
                if (response.success) {
                    $result.html('<div class="gacg-alert gacg-alert-success">✅ ' + response.data + '</div>');
                } else {
                    $result.html('<div class="gacg-alert gacg-alert-error">❌ ' + (response.data || 'Lỗi không xác định') + '</div>');
                }
            }).fail(function() {
                GACG.setLoading($button, false);
                $result.html('<div class="gacg-alert gacg-alert-error">❌ Lỗi kết nối mạng</div>');
            });
        },

        setLoading: function($element, loading) {
            if (loading) {
                $element.addClass('gacg-loading').prop('disabled', true);
                $element.data('original-text', $element.text());
                $element.text('Đang xử lý...');
            } else {
                $element.removeClass('gacg-loading').prop('disabled', false);
                $element.text($element.data('original-text') || 'Thực hiện');
            }
        },

        showAlert: function(message, type) {
            type = type || 'info';
            
            var alertClass = 'gacg-alert-' + type;
            var $alert = $('<div class="gacg-alert ' + alertClass + '" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">' + 
                message + 
                '<button type="button" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer; margin-left: 10px;">&times;</button>' +
            '</div>');
            
            $alert.find('button').on('click', function() {
                $alert.fadeOut(function() {
                    $alert.remove();
                });
            });
            
            $('body').append($alert);
            
            setTimeout(function() {
                $alert.fadeOut(function() {
                    $alert.remove();
                });
            }, 5000);
        },

        autoSave: function() {
            var data = {
                title: $('#gacg-title').val() || '',
                content: $('#gacg-content').val() || '',
                title_list: $('#gacg-title-list').val() || '',
                timestamp: Date.now()
            };
            
            if (data.title || data.content || data.title_list) {
                localStorage.setItem('gacg_auto_save', JSON.stringify(data));
            }
        },

        restoreAutoSave: function() {
            var autoSave = localStorage.getItem('gacg_auto_save');
            
            if (autoSave) {
                try {
                    var data = JSON.parse(autoSave);
                    var hoursDiff = (Date.now() - data.timestamp) / (1000 * 60 * 60);
                    
                    if (hoursDiff < 24) {
                        var hasData = data.title || data.content || data.title_list;
                        
                        if (hasData && confirm('Phát hiện dữ liệu đã lưu tự động. Bạn có muốn khôi phục không?')) {
                            if (data.title) $('#gacg-title').val(data.title);
                            if (data.content) $('#gacg-content').val(data.content);
                            if (data.title_list) $('#gacg-title-list').val(data.title_list).trigger('input');
                            
                            GACG.showAlert('✅ Đã khôi phục dữ liệu từ lần trước', 'success');
                        }
                    } else {
                        localStorage.removeItem('gacg_auto_save');
                    }
                } catch (e) {
                    localStorage.removeItem('gacg_auto_save');
                }
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        GACG.init();
        GACG.restoreAutoSave();
        
        setInterval(GACG.autoSave, 30000);
        
        $(window).on('beforeunload', function() {
            GACG.autoSave();
        });
        
        var now = new Date();
        now.setHours(now.getHours() + 1);
        var datetime = now.toISOString().slice(0, 16);
        $('#gacg-start-date').attr('min', datetime);
        
        if (!$('#gacg-start-date').val()) {
            $('#gacg-start-date').val(datetime);
        }
    });

})(jQuery);