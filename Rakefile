require "pathname"

directory "tmp"
BASE = Pathname.new(File.expand_path(File.dirname(__FILE__)))

task :clean do
  rm_rf "tmp/adgear-wp-plugin.zip"
end

task :build => "tmp" do
  cmdline = "/usr/bin/zip -FS -1 -r --latest-time"
  cmdline << " #{BASE + "tmp/adgear-wp-plugin.zip"}"
  cmdline << " #{BASE.basename}"
  [BASE + "tmp/\\*", BASE + ".git/\\*"].each do |exclusion|
    cmdline << " --exclude #{exclusion.to_s.sub(BASE.parent.to_s + "/", "")}"
  end

  chdir(BASE + "..") do
    sh cmdline
  end
end

task :push do
  sh "/usr/bin/scp #{BASE + "adgear-ad-manager.php"} fbwp@cortes.dreamhost.com:wp.teksol.info/wp-content/plugins/"
end

task :default => :push
