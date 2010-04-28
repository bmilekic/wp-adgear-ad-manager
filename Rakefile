require "pathname"

directory "tmp"

task :clean do
  rm_rf "tmp/adgear-wp-plugin.zip"
end

task :build => "tmp" do
  base = Pathname.new(File.expand_path(File.dirname(__FILE__)))
  sh "/usr/bin/zip -FS -1 -r --latest-time tmp/adgear-wp-plugin.zip #{base} --exclude #{base + "tmp/\\*"} --exclude #{base + ".git/\\*"}"
end

task :default => :build
