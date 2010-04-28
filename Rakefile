require "pathname"

directory "tmp"

task :clean do
  rm_rf "tmp/adgear-wp-plugin.zip"
end

task :build => "tmp" do
  base = Pathname.new(File.expand_path(File.dirname(__FILE__)))
  cmdline = "/usr/bin/zip -FS -1 -r --latest-time"
  cmdline << " #{base + "tmp/adgear-wp-plugin.zip"}"
  cmdline << " #{base.basename}"
  [base + "tmp/\\*", base + ".git/\\*"].each do |exclusion|
    cmdline << " --exclude #{exclusion.to_s.sub(base.parent.to_s + "/", "")}"
  end

  chdir(base + "..") do
    sh cmdline
  end
end

task :default => :build
