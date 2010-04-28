directory "tmp"

task :build => "tmp" do
  sh "/usr/bin/zip -FS -1 -r --latest-time tmp/adgear-wp-plugin.zip #{File.expand_path(File.dirname(__FILE__))} --exclude tmp/\* --exclude tmp/.git/\*"
end

task :default => :build
