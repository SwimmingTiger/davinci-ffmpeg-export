# 用 ffmpeg -copy 导出达芬奇视频工程，避免重新编码

Davince Resolve 自带的导出功能会对视频进行转码，速度慢且视频质量可能会下降。
如果视频工程只是简单的剪切合并，没有转场或特效，就可以用该脚本通过ffmpeg无损导出。

## 使用方法：
1. 在 Davince Resolve 选择“文件” > “导出” > “时间线”，导出为“FCP 7 XML V5文件”。
2. 安装依赖包：
     ```
     sudo apt install ffmpeg php-cli php-xml
     ```
3. 使用该脚本导出视频文件，用法：
     ```
     ./export.php fcp-7-xml-v5-timeline.xml output.mkv
     ```

注意：除片段开始结束时间之外的所有其他设置都不会生效。
     该脚本不会修改视频内容，只会根据时间线XML文件对视频进行剪切拼接。
     如需修改视频内容（添加转场等），请用达芬奇导出。
