<div class="barleft">
<div class="barright">
<p><b>Finding Source Information</b>
</div>
</div>
<table border="0" width="95%" align="center">
<tr>
    <td align="left"><font color="blue">*</font> = Required Field</td>
</tr>
</table>
<table width="98%" align="center" border="0" cellpadding="0" cellspacing="0" class="tbframe">
<form name="edit" method="post" action="/panel/source/sub/update/id/<?php echo $this->id;?>">
    <tr>
        <td align="right" class="thc" width="200">Source Name:</td>
        <td class="tdc">&nbsp;<input type="text" name="source_name" size="90"
            value="<?php echo $this->source['name'];?>"><font color="blue"> *</font></td>
    </tr>
    <tr>
        <td align="right" class="thc">Nickname:</td>
        <td class="tdc">&nbsp;<input type="text" name="source_nickname" size="90"
            value="<?php echo $this->source['nickname'];?>"><font color="blue"> *</font></td>
    </tr>
    <tr>
        <td align="right" class="thc" width="200">Description:</td>
        <td class="tdc">&nbsp;<textarea name="source_desc" size="30" cols="110" rows="5"><?php echo $this->source['desc'];?></textarea></td>
    </tr>
   </table>
<br>
<br>
<table border="0" width="300">
<tr align="center">
    <td><input type="submit" value="Update" title="submit your request"></td>
    <td><span style="cursor: pointer"><input type="reset" value="Reset"></span></td>
</tr>
</table>
</form>
<br>
