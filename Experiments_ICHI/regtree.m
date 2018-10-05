function regtree(direc)
list = dir([direc '\*.csv']);
mkdir([direc,'\results']);

for i=1:size(list)
disp(list(i).name);
file = csvread([direc '\' list(i).name]);

 
end
end