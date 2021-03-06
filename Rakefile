require "pathname"

def version
  return @version if @version
  vstring = File.read("adgear-ad-manager.php").split("\n").grep(/\*\s+Version:\s/).first
  @version = vstring.split(":").last.strip
end

directory "tmp"
BASE = Pathname.new(File.expand_path(File.dirname(__FILE__)))

task :clean do
  rm "REVISION"
  rm_rf "tmp/adgear-ad-manager-#{version}.zip"
end

file "REVISION" do
  sh "git show --pretty=oneline | head -n 1 | cut -c -40 > REVISION"
end

file "tmp/adgear-ad-manager-#{version}.zip" => "REVISION" do
  cmdline = "/usr/bin/zip -FS -1 -r --latest-time"
  cmdline << " #{BASE + "tmp/adgear-ad-manager-#{version}.zip"}"
  cmdline << " #{BASE.basename}"
  [BASE + "tmp/\\*", BASE + ".git/\\*"].each do |exclusion|
    cmdline << " --exclude #{exclusion.to_s.sub(BASE.parent.to_s + "/", "")}"
  end
  cmdline << " --exclude .DS_Store"
  cmdline << " --exclude .gitignore"

  chdir(BASE + "..") do
    sh cmdline
  end
end

task :build => ["tmp", "tmp/adgear-ad-manager-#{version}.zip"]

task :push do
  sh "/usr/bin/scp #{BASE + "adgear-ad-manager.php"} fbwp@cortes.dreamhost.com:wp.teksol.info/wp-content/plugins/"
end

task :default => :push
