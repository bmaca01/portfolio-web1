<?php
$currentPage = 'contact';
$htmlTitle = 'benjmacaro.dev - contact';
$metaDescription = 'Contact BenjMacaro for web development, collaborations, or just to say hi!';
$metaKeywords = 'contact, email, hire, freelance, web developer';
include 'includes/page-header.php';
?>

<!-- Main Contact Box -->
<h2 class="section-heading">Let's Connect!</h2>
<table width="100%" cellpadding="15" cellspacing="0">
    <tr>
        <td class="text-primary text-center" width="50%">
            <strong>Email:</strong><br>
            <a href="mailto:info@benjmacaro.dev">info@benjmacaro.dev</a><br>
        </td>
        <td class="text-primary text-center" width="50%">
            <strong>GitHub:</strong><br>
            <a href="https://github.com/bmaca01">@bmaca01</a><br>
        </td>
    </tr>
</table>

<br>

<h2 class="section-heading">Send Me a Message</h2>
<form action="#" method="post" name="contact">
    <fieldset disabled>
    <table width="100%" cellpadding="5" cellspacing="0">
        <tr>
            <td class="form-label" width="30%" align="right">
                <strong>Your Name:</strong>
            </td>
            <td width="70%">
                <input class="form-input" type="text" name="name" size="40">
            </td>
        </tr>
        <tr>
            <td class="form-label" align="right">
                <strong>Your Email:</strong>
            </td>
            <td>
                <input class="form-input" type="text" name="email" size="40">
            </td>
        </tr>
        <tr>
            <td class="form-label" align="right">
                <strong>Subject:</strong>
            </td>
            <td>
                <select class="form-select" name="subject">
                    <option>Freelance Project</option>
                    <option>Full-Time Opportunity</option>
                    <option>Collaboration</option>
                    <option>Bug Report</option>
                    <option>Just Saying Hi!</option>
                    <option>Link Exchange</option>
                    <option>Other</option>
                </select>
            </td>
        </tr>
        <tr>
            <td class="form-label" align="right" valign="top">
                <strong>Message:</strong>
            </td>
            <td>
                <textarea class="form-textarea" name="message" rows="8" cols="50"></textarea>
            </td>
        </tr>
        <tr>
            <td class="form-label" align="right">
                <strong>How did you find me?</strong>
            </td>
            <td class="text-primary">
                <input type="radio" name="found" value="search"> Search Engine
                <input type="radio" name="found" value="webring"> Web Ring
                <input type="radio" name="found" value="friend"> Friend
                <input type="radio" name="found" value="other"> Other
            </td>
        </tr>
        <tr>
            <td colspan="2" align="center">
                <br>
                <input class="form-button" type="submit" value="Send Message!">
                &nbsp;&nbsp;
                <input class="form-button" type="reset" value="Clear">
            </td>
        </tr>
    </table>
    </fieldset>
</form>

<br>

<p class="body-text">
    <strong>Current Status:</strong> Available for hire
</p>

<br>

<!-- Social Media -->
<h2 class="section-heading">Socials</h2>
<p class="body-text">
    <a href="https://linkedin.com/in/benedikt-macaro">LinkedIn</a> |
    <a href="https://www.instagram.com/bn.mcr/">Instagram</a> |
    <a href="https://open.spotify.com/user/myemail12318-us?si=290ff16a78ab4c2d">Spotify</a>
</p>

<?php include 'includes/page-footer.php'; ?>
