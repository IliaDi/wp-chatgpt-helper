<?php
function chatgpt_shortcode() {
    ?>
    <div id="chatbot-chatgpt">
        <div id="chatbot-chatgpt-header">
            <div id="chatgptTitle" class="title"> WP ChatGPT Helper </div>
        </div>
        <div id="chatbot-chatgpt-conversation"></div>
        <div id="chatbot-chatgpt-input">
            <input type="text" id="chatbot-chatgpt-message" placeholder="<?php echo esc_attr( 'Type your message...' ); ?>">
            <button id="chatbot-chatgpt-submit">Send</button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('chatgpt_helper', 'chatgpt_shortcode');