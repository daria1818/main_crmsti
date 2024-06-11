<html>
<head><title>Создание ссылок на папки bitrix, local и upload</title></head>
<body>
<?
error_reporting(E_ALL & ~E_NOTICE);
@ini_set("display_errors",1);

if ($_POST['path'])
   $path = rtrim($_POST['path'],"/\\");
else
   $path = '../dev.stionline.ru';

if ($_POST['create'])
{
   if (preg_match("#^/#",$path))
      $full_path = $path;
   else
      $full_path = realpath($_SERVER['DOCUMENT_ROOT'].'/'.$path);

   if (file_exists($_SERVER['DOCUMENT_ROOT']."/bitrix"))
      $strError = "В текущей папке уже существует папка bitrix";
   elseif (is_dir($full_path))
   {
      if (is_dir($full_path."/bitrix"))
      {
         if (symlink($path."/bitrix",$_SERVER['DOCUMENT_ROOT']."/bitrix"))
         {
            if (symlink($path."/upload",$_SERVER['DOCUMENT_ROOT']."/upload"))
             {
               if (symlink($path."/local",$_SERVER['DOCUMENT_ROOT']."/local"))
                  echo "Символические ссылки удачно созданы";
               else
               $strError = 'Не удалось создать ссылку на папку local, обратитесь к администратору сервера';
              }
           else
           $strError = 'Не удалось создать ссылку на папку upload, обратитесь к администратору сервера';
          }
          else
          $strError = 'Не удалось создать ссылку на папку bitrix, обратитесь к администратору сервера';           
      }
      else
         $strError = 'Указанный путь не содержит папку bitrix';
   }
   else
      $strError = 'Неверно указан путь или ошибка прав доступа';
   
   if ($strError)
      echo ''.$strError.'
Исходный путь: '.$full_path;
}
?>
<form method=post>
Путь к папке, содержащей папки bitrix, local и upload: <input name=path  value="<?=htmlspecialchars($path)?>"><br>
<input type=submit value='Создать' name=create>
</form>
</body> 
</html>