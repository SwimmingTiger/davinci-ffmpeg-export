#!/usr/bin/env php
<?php
/**
 * 用 ffmpeg -copy 导出达芬奇视频工程，避免重新编码
 *
 * Davince Resolve 自带的导出功能会对视频进行转码，速度慢且视频质量可能会下降。
 * 如果视频工程只是简单的剪切合并，没有转场或特效，就可以用该脚本通过ffmpeg无损导出。
 * 
 * 使用方法：
 * 1. 在 Davince Resolve 选择“文件” > “导出” > “时间线”，导出为“FCP 7 XML V5文件”。
 * 2. 使用该脚本导出视频文件，用法：
 *      ./export.php fcp-7-xml-v5-timeline.xml output.mkv
 * 
 * 注意：除片段开始结束时间之外的所有其他设置都不会生效。
 *      该脚本不会修改视频内容，只会根据时间线XML文件对视频进行剪切拼接。
 *      如需修改视频内容（添加转场等），请用达芬奇导出。
**/

# 可执行文件的路径
define('FFMPEG', 'ffmpeg');
define('WSLPATH', '/usr/bin/wslpath');

# 临时文件夹路径
define('TMP_DIR', '.');

function usage()
{
    global $argv;
    echo "Usage: $argv[0] fcp-7-xml-v5-timeline.xml output.mkv\n";
    exit;
}

if ($argc != 3) {
    usage();
}

$xml = $argv[1];
$output = $argv[2];
$pid = getmypid();

if (!is_file($xml)) {
    usage();
}

$obj = new SimpleXMLElement(file_get_contents($xml));

$timeBase = $obj->sequence->rate->timebase;

$i = 1;
$list = [];
$files = [];
foreach ($obj->sequence->media->video->track->clipitem as $clip) {
    $id = (string) ($clip->file['id']);
    $url = $clip->file->pathurl;

    $path = escapeshellarg(pathUrlDecode($id, $url));
    $start = escapeshellarg($clip->in / $timeBase);
    $end = escapeshellarg($clip->out / $timeBase);

    $tmpFile = TMP_DIR."/tmp-$pid-$i.mkv";
    $list[] = "file '$tmpFile'";
    $files[] = $tmpFile;

    $cmd = FFMPEG." -hide_banner -i $path -ss $start -to $end -c copy $tmpFile -y";
    echo "********************************\n$cmd\n";
    passthru($cmd);
    echo "\n";

    $i++;
}

$mergeList = TMP_DIR."/merge-$pid.list";
file_put_contents($mergeList, implode("\n", $list));

$cmd = FFMPEG." -hide_banner -f concat -safe 0 -i $mergeList -c copy ".escapeshellarg($output)." -y";
echo "********************************\n$cmd\n";
passthru($cmd);
echo "\n";

unlink($mergeList);
foreach ($files as $file) {
    unlink($file);
}

function pathUrlDecode($id, $url)
{
    static $urlCache = [];

    if (isset($urlCache[$id])) {
        return $urlCache[$id];
    }

    $originUrl = $url;

    if (!is_file($url) && substr($url, 0, 17) == 'file://localhost/') {
        $url = substr($url, 17);
    }
    if (!is_file($url) && preg_match('#%[a-f0-9]{2}#is', $url)) {
        $url = urldecode($url);
    }
    // 转换WSL路径：C:/xxx -> /mnt/c/xxx
    if (!is_file($url) && preg_match('#^([a-z]):(.+)$#is', $url, $opt) && is_file(WSLPATH)) {
        $url = system(WSLPATH.' '.escapeshellarg($url));
    }

    if (!is_file($url)) {
        die("无法解析媒体文件URL，请手动修改XML中的文件路径。\n有问题的URL：$originUrl\n解析后：$url\n");
    }

    $urlCache[$id] = $url;
    return $url;
}
