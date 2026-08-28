<?php
/***********************
 * INPUT
 ***********************/
$docno = $_REQUEST['docno'];
$rowCount = $_REQUEST['rows'];   // <<< NUMBER OF ROWS COMING DYNAMICALLY

/***********************
 * PDF PATH
 ***********************/
$tpt = 'VR_ORDER/unsigned/VRNO_'.$docno.'.pdf';

/***********************
 * DYNAMIC COORDINATE LOGIC
 ***********************/

// A4 PDF height
$pageHeight = 842;

// Footer text baseline (where "Principal Chief Medical Officer" exists)
$footerBaseY = 90;   // adjust once if needed

// Gap between footer text and signature
$gap = 25;

// Signature box size
$signWidth  = 160;
$signHeight = 45;

// X position (right side, aligned to designation)
$x1 = 360;
$x2 = $x1 + $signWidth;

// Y position (dynamic, bottom anchored)
$y1 = $footerBaseY + $gap;
$y2 = $y1 + $signHeight;

// Final coordinate string
//$dt6 = "coordinate={$x1},{$y1},{$x2},{$y2}";
 $dt6 = "coordinate=360,570,520,615";

?>
<!DOCTYPE html>
<html>
<head>
<title>Digital Signature Signing</title>

<script src="lib/jquery.min.js"></script>
<script src="lib/jquery-ui.min.js"></script>

<script>
var connection = new WebSocket('wss://127.0.0.1:2041');

connection.onopen = function () {
    console.log('WebSocket Connected');
};

//connection.onerror = function (error) {
//    alert('Signer not running');
//};

var completeData = '';
var splitData = [];
var i = 0, j = 0, k = 0;
var bufLength = 16300;
var actualData = '';
var textId = '';

function setData(txf1, msg) {
    actualData = msg;
    textId = txf1;
    completeData = msg;

    if (completeData.length < bufLength) {
        splitData[0] = msg;
        call(txf1, msg);
    } else {
        var t = 0;
        for (i = 0; i < completeData.length / bufLength; i++) {
            splitData[i] = completeData.substring(t, t + bufLength);
            t += bufLength;
        }
        call(txf1, msg);
    }
}

function call(txf1, msg) {

    if (msg.length < bufLength) {
        connection.send(splitData[0] + 'completed');
    } else {
        if (j == i - 1) {
            connection.send(splitData[j] + 'completed');
        } else {
            connection.send(splitData[j]);
        }
        j++;
    }

    connection.onmessage = function (e) {

        if (e.data == 'sendmore') {
            call(textId, actualData);
            return;
        }

        if (e.data.indexOf('completed') !== -1) {

            var dno = document.getElementById("doc").innerHTML;

            $.ajax({
                url: 'digi_sign_vr2.php?document=' + dno,
                type: 'POST',
                data: { dt: JSON.stringify(e.data) },
                success: function (data) {
                    document.getElementById("btn").value = "Signed";
                    document.getElementById("btn").disabled = true;
                    document.getElementById("docnn").src = data;
                }
            });
        }
    };
}
</script>

<style>
.button {
    background-color: #0000FF;
    color: white;
    padding: 15px 32px;
    font-size: 16px;
    cursor: pointer;
}
</style>
</head>

<body>

<table width="1000" align="center">
<tr>
<td colspan="12">
<iframe id="docnn" width="99%" height="560px"
src="<?php echo $tpt; ?>"></iframe>
</td>
</tr>

<p id="doc" hidden><?php echo $docno; ?></p>

<textarea id="cont" hidden>
<?php
$dt  = 'action=signpdf';
$stt = base64_encode(file_get_contents($tpt));

echo $dt."\n";
echo "datatosign=".$stt."\n";
echo "signaction=3\n";
echo "outputpath=\n";
echo "signtype=sign\n";
echo "expirycheck=true\n";
echo $dt6."\n";             // <<< DYNAMIC COORDINATE
echo "issuername=\n";
echo "certtype=DSC\n";
echo "certclass=2|3\n";
echo "pageno=last\n";
?>
</textarea>

<center>
<input id="btn" class="button" type="button" value="Sign"
onclick='setData("signData",document.getElementById("cont").value)'>
</center>

<textarea id="signData" hidden></textarea>

</table>

</body>
</html>
