<?php
session_start();

// Handle login
if(isset($_POST['username']) && !empty(trim($_POST['username']))) {
    $_SESSION['username'] = trim($_POST['username']);
    header('Location: index.php');
    exit;
}

// Handle logout
if(isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<html>
<head>
        <title>Simple Chat Room</title>
        <link href='http://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,400,300' rel='stylesheet' type='text/css'>
        <link rel="stylesheet" href="css/style.css" />
        <script type="text/javascript" src="js/jquery-3.7.1.min.js" ></script>
</head>
<body>
<div class='header'>
        <h1>
                SIMPLE CHAT ROOM
                <?php if(isset($_SESSION['username'])) { ?>
                        <div class='header-controls'>
                                <button id='toggleUpdate' onclick='toggleAutoUpdate()' title='Pause auto-update'>⏸</button>
                                <a class='logout' href="?logout">Logout</a>
                        </div>
                <?php } ?>
        </h1>
</div>

<div class='main'>
<?php if(isset($_SESSION['username'])) { ?>
<div id='result'></div>
<div class='chatcontrols'>
        <form method="post" onsubmit="return submitchat();">
        <textarea name='chat' id='chatbox' autocomplete="off" placeholder="ENTER CHAT HERE" rows="3"></textarea>
        <input type='submit' name='send' id='send' class='btn btn-send' value='Send' />
        <input type='button' name='clear' class='btn btn-clear' id='clear' value='X' title="Clear Chat" / >
</form>
<script>
// Auto-update control
let autoUpdatePaused = false;
let isUserSelecting = false;
let lastUpdateTime = 0;

// Function to toggle auto-update
function toggleAutoUpdate() {
    autoUpdatePaused = !autoUpdatePaused;
    const toggleBtn = document.getElementById('toggleUpdate');
    if (autoUpdatePaused) {
        toggleBtn.innerHTML = '▶';
        toggleBtn.title = 'Resume auto-update';
        toggleBtn.style.backgroundColor = '#8fe4ad';
    } else {
        toggleBtn.innerHTML = '⏸';
        toggleBtn.title = 'Pause auto-update';
        toggleBtn.style.backgroundColor = '';
        getChatUpdates(); // Immediate update when resuming
    }
}

// Track user selection
document.addEventListener('mousedown', function() {
    isUserSelecting = true;
});

document.addEventListener('mouseup', function() {
    setTimeout(() => {
        isUserSelecting = false;
    }, 100);
});

// Function to copy message text to clipboard
function copyMessage(button) {
    const messageDiv = button.parentElement;
    const messageContent = messageDiv.querySelector('.message-content');
    
    // Get the raw text without HTML tags
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = messageContent.innerHTML;
    const rawText = tempDiv.textContent || tempDiv.innerText || '';
    
    // Create temporary textarea for copying
    const textarea = document.createElement('textarea');
    textarea.value = rawText.trim();
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            // Visual feedback
            const originalText = button.innerHTML;
            button.innerHTML = '✓';
            button.style.backgroundColor = '#8fe4ad';
            button.style.color = 'white';
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.backgroundColor = '';
                button.style.color = '';
            }, 2000);
        }
    } catch (err) {
        console.error('Failed to copy text: ', err);
        // Fallback: alert with text
        alert('Failed to copy message. Here is the text:\n\n' + rawText);
    }
    
    document.body.removeChild(textarea);
}

// Function to get chat updates
function getChatUpdates() {
    if (autoUpdatePaused || isUserSelecting) {
        return;
    }
    
    const now = Date.now();
    if (now - lastUpdateTime < 1000) {
        return;
    }
    
    $.ajax({
        url: 'chat.php',
        data: {ajaxget: true},
        method: 'post',
        success: function(data) {
            if (!$('#result').is(':focus') && !isUserSelecting) {
                const currentScroll = $('#result').scrollTop();
                const isScrolledToBottom = currentScroll + $('#result').innerHeight() >= $('#result')[0].scrollHeight - 10;
                
                $('#result').html(data);
                
                if (isScrolledToBottom) {
                    document.getElementById('result').scrollTop = document.getElementById('result').scrollHeight;
                } else {
                    $('#result').scrollTop(currentScroll);
                }
            }
            lastUpdateTime = Date.now();
        }
    });
}

// Javascript function to submit new chat entered by user
function submitchat(){
    if($('#chatbox').val().trim()=='') return false;
    $.ajax({
        url:'chat.php',
        data:{chat:$('#chatbox').val(),ajaxsend:true},
        method:'post',
        success:function(data){
            $('#result').html(data);
            $('#chatbox').val('');
            document.getElementById('result').scrollTop = document.getElementById('result').scrollHeight;
        }
    })
    return false;
};

// Auto-update interval
setInterval(getChatUpdates, 1500);

// Proper keydown handler for textarea
$('#chatbox').keydown(function(e) {
    if (e.key === 'Enter' && e.shiftKey) {
        // Shift+Enter - allow newline in textarea
        return true;
    } else if (e.key === 'Enter') {
        // Enter alone - submit form
        e.preventDefault();
        submitchat();
        return false;
    }
});

// Function to clear chat history
$(document).ready(function(){
        $('#clear').click(function(){
                if(!confirm('Are you sure you want to clear chat?'))
                        return false;
                $.ajax({
                        url:'chat.php',
                        data:{username:"<?php echo $_SESSION['username'] ?>",ajaxclear:true},
                        method:'post',
                        success:function(data){
                                $('#result').html(data);
                        }
                })
        })
})
</script>
<?php } else { ?>
<div class='userscreen'>
        <form method="post">
                <input type='text' class='input-user' placeholder="ENTER YOUR NAME HERE" name='username' />
                <input type='submit' class='btn btn-user' value='START CHAT' />
        </form>
</div>
<?php } ?>

</div>
</body>
</html>
