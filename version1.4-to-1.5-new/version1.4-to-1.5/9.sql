Create or replace function random_string(length integer) returns text as
$$
declare
 chars text[] := '{2,3,4,6,7,8,a,b,c,d,e,f,g,h,j,k,m,n,p,r,t,u,v,w,x,y,z}';
 result text := '';
 i integer := 0;
begin
 for i in 1..length loop
   result := result || chars[1+random()*(array_length(chars, 1)-1)];
 end loop;
 return result;
end;
$$ language plpgsql;



insert into patient_social_needs (id,patient_id, organization_id, created_by, created_on_time, created_on_date, object_path)
select concat('patientSocialNeeds:',random_string(7)),id, organization_id, created_by, to_char(now(), 'HH24:MI:SS') , current_date, ltree (replace(id, ':', '__'))
from patient
where id not in (
   select patient_id from patient_social_needs
   );
