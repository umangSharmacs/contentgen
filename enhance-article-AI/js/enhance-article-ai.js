jQuery(document).ready(function($) {
    
    // Handle the Enhance by AI button click
    $('#enhance-article-ai-btn').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $buttonText = $button.find('.button-text');
        var $spinner = $button.find('.spinner');
        var originalText = $buttonText.text();
        
        // Get the post content and title
        var postContent = '';
        var postTitle = '';
        
        // Try to get content from different editors
        if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
            // Gutenberg editor
            postContent = wp.data.select('core/editor').getEditedPostContent();
            postTitle = wp.data.select('core/editor').getEditedPostAttribute('title');
        } else if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content')) {
            // Classic editor with TinyMCE
            postContent = tinyMCE.get('content').getContent();
            postTitle = $('#title').val();
        } else {
            // Fallback to textarea
            postContent = $('#content').val();
            postTitle = $('#title').val();
        }
        
        if (!postContent.trim()) {
            alert('Please add some content to your post before enhancing.');
            return;
        }
        
        // Show loading state
        $button.prop('disabled', true);
        $buttonText.text(enhanceArticleAI.enhancing_text);
        $spinner.show();
        
        // Send AJAX request
        $.ajax({
            url: enhanceArticleAI.ajax_url,
            type: 'POST',
            data: {
                action: 'enhance_article_ai',
                nonce: enhanceArticleAI.nonce,
                content: postContent,
                title: postTitle
            },
            success: function(response) {
                if (response.success) {
                    showEnhancedContent(response.data, postContent, postTitle);
                } else {
                    alert('Error: ' + (response.data || 'Unknown error occurred'));
                }
            },
            error: function(xhr, status, error) {
                alert('Request failed: ' + error);
            },
            complete: function() {
                // Reset button state
                $button.prop('disabled', false);
                $buttonText.text(originalText);
                $spinner.hide();
            }
        });
    });
    
    function showEnhancedContent(enhancedData, originalContent, originalTitle) {
        // Create modal for enhanced content
        var modalHtml = `
            <div id="enhanced-content-modal" class="enhanced-content-modal">
                <div class="enhanced-content-modal-overlay"></div>
                <div class="enhanced-content-modal-content">
                    <div class="enhanced-content-modal-header">
                        <h2>Enhanced Content</h2>
                        <button type="button" class="enhanced-content-modal-close">&times;</button>
                    </div>
                    <div class="enhanced-content-modal-body">
                        <div class="enhanced-content-tabs">
                            <button type="button" class="enhanced-content-tab active" data-tab="enhanced">Enhanced Version</button>
                            <button type="button" class="enhanced-content-tab" data-tab="original">Original Version</button>
                        </div>
                        <div class="enhanced-content-panel active" id="enhanced-panel">
                            <div class="enhanced-content-section">
                                <h3>Enhanced Text:</h3>
                                <div class="enhanced-text" contenteditable="true">${enhancedData.enhanced_text || 'No enhanced text received'}</div>
                            </div>
                            
                            <div class="enhanced-content-section">
                                <h3>Category:</h3>
                                <div class="enhanced-category" contenteditable="true">${enhancedData.category || 'No category assigned'}</div>
                            </div>
                            
                            <div class="enhanced-content-section">
                                <h3>Subcategory:</h3>
                                <div class="enhanced-subcategory" contenteditable="true">${enhancedData.subcategory || 'No subcategory assigned'}</div>
                            </div>
                            
                            <div class="enhanced-content-section">
                                <h3>Tags:</h3>
                                <div class="enhanced-tags" contenteditable="true">${enhancedData.tags || 'No tags assigned'}</div>
                            </div>
                            
                            <div class="enhanced-content-section">
                                <h3>Pillar Page:</h3>
                                <div class="enhanced-pillar-page">
                                    <input type="checkbox" id="pillar-page-checkbox" ${enhancedData.pillar_page ? 'checked' : ''} />
                                    <label for="pillar-page-checkbox">Mark as pillar page</label>
                                </div>
                            </div>
                            
                            ${enhancedData.debug_info ? `
                            <div class="enhanced-content-section debug-info">
                                <h3>Debug Information:</h3>
                                <div class="debug-text">${enhancedData.debug_info}</div>
                            </div>
                            ` : ''}
                        </div>
                        <div class="enhanced-content-panel" id="original-panel">
                            <h3>Original Title:</h3>
                            <div class="original-title">${originalTitle}</div>
                            <h3>Original Content:</h3>
                            <div class="original-content">${originalContent}</div>
                        </div>
                    </div>
                    <div class="enhanced-content-modal-footer">
                        <button type="button" class="button button-secondary" id="copy-enhanced">Copy Enhanced</button>
                        <button type="button" class="button button-primary" id="apply-enhanced">Apply to Post</button>
                        <button type="button" class="button button-secondary" id="close-modal">Close</button>
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to page
        $('body').append(modalHtml);
        
        // Handle tab switching
        $('.enhanced-content-tab').on('click', function() {
            var tabName = $(this).data('tab');
            $('.enhanced-content-tab').removeClass('active');
            $('.enhanced-content-panel').removeClass('active');
            $(this).addClass('active');
            $('#' + tabName + '-panel').addClass('active');
        });
        
        // Handle copy enhanced content
        $('#copy-enhanced').on('click', function() {
            var enhancedText = $('.enhanced-text').text();
            var category = $('.enhanced-category').text();
            var subcategory = $('.enhanced-subcategory').text();
            var tags = $('.enhanced-tags').text();
            var pillarPage = $('#pillar-page-checkbox').is(':checked');
            
            var textToCopy = 'Enhanced Text:\n' + enhancedText + '\n\n';
            textToCopy += 'Category: ' + category + '\n';
            textToCopy += 'Subcategory: ' + subcategory + '\n';
            textToCopy += 'Tags: ' + tags + '\n';
            textToCopy += 'Pillar Page: ' + (pillarPage ? 'Yes' : 'No');
            
            navigator.clipboard.writeText(textToCopy).then(function() {
                alert('Enhanced content copied to clipboard!');
            }).catch(function() {
                // Fallback for older browsers
                var textArea = document.createElement('textarea');
                textArea.value = textToCopy;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('Enhanced content copied to clipboard!');
            });
        });
        
        // Handle apply enhanced content
        $('#apply-enhanced').on('click', function() {
            var enhancedText = $('.enhanced-text').html();
            var category = $('.enhanced-category').text();
            var subcategory = $('.enhanced-subcategory').text();
            var tags = $('.enhanced-tags').text();
            var pillarPage = $('#pillar-page-checkbox').is(':checked');
            
            // Apply to the editor
            if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch('core/editor')) {
                // Gutenberg editor - apply the enhanced text as content
                wp.data.dispatch('core/editor').editPost({
                    content: enhancedText
                });
                
                // You might want to add custom fields for category, subcategory, tags, and pillar page
                // This would require additional setup for custom fields
                console.log('Category:', category);
                console.log('Subcategory:', subcategory);
                console.log('Tags:', tags);
                console.log('Pillar Page:', pillarPage);
                
            } else if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content')) {
                // Classic editor with TinyMCE
                tinyMCE.get('content').setContent(enhancedText);
            } else {
                // Fallback to textarea
                $('#content').val(enhancedText);
            }
            
            alert('Enhanced content applied to your post!');
            $('#enhanced-content-modal').remove();
        });
        
        // Handle close modal
        $('.enhanced-content-modal-close, #close-modal, .enhanced-content-modal-overlay').on('click', function() {
            $('#enhanced-content-modal').remove();
        });
        
        // Handle escape key
        $(document).on('keydown.enhancedModal', function(e) {
            if (e.keyCode === 27) { // ESC key
                $('#enhanced-content-modal').remove();
                $(document).off('keydown.enhancedModal');
            }
        });
    }
}); 